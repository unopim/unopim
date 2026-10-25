<?php

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\DocumentStubPayloadBuilder;
use Webkul\User\Models\Admin;

beforeEach(function (): void {
    config()->set('publication.types.dpp', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => DocumentStubPayloadBuilder::class,
        'template'        => 'publication::dpp.show',
        'route_prefix'    => 'dpp',
    ]);
});

it('mints one release per minted version, numbered per publication, and none for an unchanged payload', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/a.pdf';
    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/b.pdf';
    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($publisher->publish($product, $channel, $complete, 'dpp'))->toBeNull();

    $publication = $first->publication->fresh();

    expect($first->release->sequence)->toBe(1)
        ->and($second->release->sequence)->toBe(2)
        ->and($second->release->published_at->equalTo($second->published_at))->toBeTrue()
        ->and($publication->releases()->count())->toBe(2)
        ->and($second->release->versions()->pluck('id')->all())->toBe([$second->id]);
});

it('numbers releases across locales in publish order and resolves the state as of each one', function (): void {
    [$product, $channel, $other, $complete] = $this->seedPassportFixture(completeBoth: true);

    $publisher = resolve(Publisher::class);

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/a.pdf';
    $completeV1 = $publisher->publish($product, $channel, $complete, 'dpp');
    $otherV1 = $publisher->publish($product, $channel, $other, 'dpp');

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/b.pdf';
    $completeV2 = $publisher->publish($product, $channel, $complete, 'dpp');

    $publication = $completeV1->publication->fresh();

    expect($completeV1->release->sequence)->toBe(1)
        ->and($otherV1->release->sequence)->toBe(2)
        ->and($completeV2->release->sequence)->toBe(3)
        ->and($completeV2->version)->toBe(2)
        ->and($otherV1->version)->toBe(1);

    $asOf = fn (int $sequence): array => $publication->releases()->where('sequence', $sequence)->firstOrFail()
        ->versionsAsOf()
        ->map(fn ($version): int => $version->id)
        ->all();

    expect($asOf(1))->toBe([$complete->id => $completeV1->id])
        ->and($asOf(2))->toEqualCanonicalizing([$complete->id => $completeV1->id, $other->id => $otherV1->id])
        ->and($asOf(3))->toEqualCanonicalizing([$complete->id => $completeV2->id, $other->id => $otherV1->id]);
});

it('mints a release for a forward-only rollback too', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/a.pdf';
    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/b.pdf';
    $publisher->publish($product, $channel, $complete, 'dpp');

    $rolledBack = $publisher->republishFrom($first, Admin::factory()->create()->id);

    expect($rolledBack->version)->toBe(3)
        ->and($rolledBack->release->sequence)->toBe(3)
        ->and($rolledBack->release->published_by_id)->not->toBeNull();
});

it('refuses to change or delete a release', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/a.pdf';
    $release = resolve(Publisher::class)->publish($product, $channel, $complete, 'dpp')->release;

    expect(fn (): mixed => $release->update(['sequence' => 99]))->toThrow(ImmutableVersionException::class)
        ->and(fn (): mixed => $release->delete())->toThrow(ImmutableVersionException::class)
        ->and($release->fresh()->sequence)->toBe(1);
});

it('refuses to move a version to another release', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/a.pdf';
    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/b.pdf';
    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect(fn (): mixed => $first->update(['release_id' => $second->release_id]))
        ->toThrow(ImmutableVersionException::class);
});

it('resolves the state as of a release in a fixed number of queries, however long the history is', function (): void {
    [$product, $channel, $other, $complete] = $this->seedPassportFixture(completeBoth: true);

    $publisher = resolve(Publisher::class);

    foreach (range(1, 6) as $round) {
        DocumentStubPayloadBuilder::$documentPath = 'publication/release/'.$round.'.pdf';
        $completeLatest = $publisher->publish($product, $channel, $complete, 'dpp');
        $otherLatest = $publisher->publish($product, $channel, $other, 'dpp');
    }

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/after.pdf';
    $publisher->publish($product, $channel, $complete, 'dpp');

    $release = $otherLatest->release->fresh();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $asOf = $release->versionsAsOf();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($release->sequence)->toBe(12)
        ->and($asOf->map(fn ($version): int => $version->id)->all())
        ->toEqualCanonicalizing([$complete->id => $completeLatest->id, $other->id => $otherLatest->id])
        ->and($asOf->every(fn ($version): bool => $version->relationLoaded('locale') && $version->relationLoaded('release')))->toBeTrue()
        ->and($queries)->toBe(3);
});

it('backfills a release per existing version and continues a backfill that stopped halfway', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $versions = collect(['a', 'b', 'c'])->map(function (string $file) use ($publisher, $product, $channel, $complete) {
        DocumentStubPayloadBuilder::$documentPath = 'publication/release/'.$file.'.pdf';

        return $publisher->publish($product, $channel, $complete, 'dpp');
    });

    $publicationId = $versions->first()->publication_id;
    $unlinked = $versions->slice(1)->pluck('id')->all();

    DB::table('publication_versions')->whereIn('id', $unlinked)->update(['release_id' => null]);
    DB::table('publication_releases')->where('publication_id', $publicationId)->where('sequence', '>', 1)->delete();

    $migration = require base_path('packages/Webkul/Publication/src/Database/Migrations/2026_09_04_000011_add_release_id_to_publication_versions_table.php');
    $migration->up();

    $sequenceOf = fn (int $versionId): int => (int) DB::table('publication_versions')
        ->join('publication_releases', 'publication_releases.id', '=', 'publication_versions.release_id')
        ->where('publication_versions.id', $versionId)
        ->value('publication_releases.sequence');

    expect($versions->map(fn ($version): int => $sequenceOf($version->id))->all())->toBe([1, 2, 3])
        ->and(DB::table('publication_releases')->where('publication_id', $publicationId)->count())->toBe(3)
        ->and(DB::table('publication_versions')->where('publication_id', $publicationId)->whereNull('release_id')->count())->toBe(0);
});

it('carries each publication\'s sequence across backfill chunks when their versions interleave', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $other = ProductProxy::factory()->create([
        'attribute_family_id' => $product->attribute_family_id,
        'values'              => $product->values,
    ]);

    $publisher = resolve(Publisher::class);

    $versions = collect([[$product, 'a'], [$other, 'a'], [$product, 'b'], [$other, 'b'], [$product, 'c']])
        ->map(function (array $publish) use ($publisher, $channel, $complete) {
            DocumentStubPayloadBuilder::$documentPath = 'publication/release/'.$publish[1].'.pdf';

            return $publisher->publish($publish[0], $channel, $complete, 'dpp');
        });

    $publicationIds = $versions->pluck('publication_id')->unique()->values()->all();

    DB::table('publication_versions')->whereIn('publication_id', $publicationIds)->update(['release_id' => null]);
    DB::table('publication_releases')->whereIn('publication_id', $publicationIds)->delete();

    $migration = require base_path('packages/Webkul/Publication/src/Database/Migrations/2026_09_04_000011_add_release_id_to_publication_versions_table.php');
    $migration->chunkSize = 2;
    $migration->up();

    $sequences = DB::table('publication_versions')
        ->join('publication_releases', 'publication_releases.id', '=', 'publication_versions.release_id')
        ->whereIn('publication_versions.id', $versions->pluck('id')->all())
        ->orderBy('publication_versions.id')
        ->pluck('publication_releases.sequence')
        ->map(fn ($sequence): int => (int) $sequence)
        ->all();

    expect($publicationIds)->toHaveCount(2)
        ->and($sequences)->toBe([1, 1, 2, 2, 3])
        ->and(DB::table('publication_releases')->whereIn('publication_id', $publicationIds)->count())->toBe(5)
        ->and(DB::table('publication_versions')->whereIn('publication_id', $publicationIds)->whereNull('release_id')->count())->toBe(0);
});

it('numbers backfilled releases by version id, not by published_at', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $versions = collect(['a', 'b', 'c'])->map(function (string $file) use ($publisher, $product, $channel, $complete) {
        DocumentStubPayloadBuilder::$documentPath = 'publication/release/'.$file.'.pdf';

        return $publisher->publish($product, $channel, $complete, 'dpp');
    });

    $publicationId = $versions->first()->publication_id;

    $versions->values()->each(fn ($version, int $index) => DB::table('publication_versions')
        ->where('id', $version->id)
        ->update(['release_id' => null, 'published_at' => now()->subDays($index)->startOfSecond()]));

    DB::table('publication_releases')->where('publication_id', $publicationId)->delete();

    $migration = require base_path('packages/Webkul/Publication/src/Database/Migrations/2026_09_04_000011_add_release_id_to_publication_versions_table.php');
    $migration->up();

    $releaseOf = fn (int $versionId) => DB::table('publication_versions')
        ->join('publication_releases', 'publication_releases.id', '=', 'publication_versions.release_id')
        ->where('publication_versions.id', $versionId)
        ->first(['publication_releases.sequence', 'publication_releases.published_at', 'publication_versions.published_at as version_published_at']);

    $releases = $versions->map(fn ($version) => $releaseOf($version->id));

    expect($releases->map(fn ($release): int => (int) $release->sequence)->all())->toBe([1, 2, 3])
        ->and($releases->every(fn ($release): bool => (string) $release->published_at === (string) $release->version_published_at))->toBeTrue();
});
