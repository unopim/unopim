<?php

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

it('mints one release per minted version, numbered per publication', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/a.pdf';
    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    DocumentStubPayloadBuilder::$documentPath = 'publication/release/b.pdf';
    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    // Unchanged payload: no version, so no release either.
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
        // Version numbers still run per locale; the release is what spans them.
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
