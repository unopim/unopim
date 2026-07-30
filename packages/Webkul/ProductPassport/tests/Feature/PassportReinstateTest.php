<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Core\Models\Locale;
use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\ProductPassport\Jobs\BulkTransitionPassportsJob;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Services\Publisher;

it('refuses to publish a locale into a withdrawn passport', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    resolve(Publisher::class)->withdraw($publication);

    $this->loginWithPermissions('all');

    Bus::fake();

    $this->postJson(route('admin.catalog.passports.publish', $publication->product_id), [
        'channel_id' => $publication->channel_id,
        'locale_ids' => [$version->locale_id],
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.publish-withdrawn'));

    Bus::assertNotDispatched(PublishPassportForProductChannelJob::class);
});

it('mints no version for another locale while the passport is withdrawn', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $publisher = resolve(Publisher::class);
    $publisher->withdraw($publication);

    $other = Locale::factory()->create();
    $publication->channel->locales()->attach($other);

    $publish = fn () => $publisher->publish(
        $publication->product,
        $publication->channel,
        $other,
        'dpp',
    );

    expect($publish)->toThrow(InvalidPublicationTransitionException::class);

    expect($publication->versions()->where('locale_id', $other->id)->count())->toBe(0);
});

it('zeroes the live locale count while withdrawn and restores it on reinstate', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    expect($publication->refresh()->live_locale_count)->toBe(1);

    $publisher = resolve(Publisher::class);

    $publisher->withdraw($publication);
    expect($publication->refresh()->live_locale_count)->toBe(0);

    $publisher->reinstate($publication);
    expect($publication->refresh()->live_locale_count)->toBe(1);
});

it('reinstates a withdrawn passport from the grid action', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    resolve(Publisher::class)->withdraw($publication);

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.reinstate', $publication->id))
        ->assertOk()
        ->assertJsonPath('message', trans('passport::app.publications.reinstated'));

    expect($publication->refresh()->status)->toBe(PublicationStatus::Published);
});

it('refuses to reinstate a passport that was never withdrawn', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.reinstate', $publication->id))
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.reinstate-invalid'));
});

it('shows the reinstate action only on a withdrawn row', function (): void {
    $this->loginWithPermissions('all');

    $grid = resolve(PublicationDataGrid::class);
    $grid->prepareActions();

    $reinstate = collect($grid->getActions())->firstWhere('index', 'reinstate');

    expect(($reinstate->condition)((object) ['id' => 1, 'status_code' => PublicationStatus::Withdrawn->value]))->toBeTrue()
        ->and(($reinstate->condition)((object) ['id' => 1, 'status_code' => PublicationStatus::Published->value]))->toBeFalse();
});

it('queues a mass transition for the selected rows', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    $this->loginWithPermissions('all');

    Bus::fake();

    $this->postJson(route('admin.catalog.passports.mass_transition'), [
        'indices' => [$publication->id],
        'value'   => PublicationStatus::Withdrawn->value,
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('passport::app.publications.mass-withdraw-queued', ['count' => 1]));

    Bus::assertDispatched(BulkTransitionPassportsJob::class);
});

it('withdraws only the published rows of a mass selection', function (): void {
    $published = $this->publishedPassportFixture()->publication;
    $alreadyWithdrawn = $this->publishedPassportFixture()->publication;

    $publisher = resolve(Publisher::class);
    $publisher->withdraw($alreadyWithdrawn);

    (new BulkTransitionPassportsJob([$published->id, $alreadyWithdrawn->id], PublicationStatus::Withdrawn))
        ->handle($publisher);

    expect($published->refresh()->status)->toBe(PublicationStatus::Withdrawn)
        ->and($alreadyWithdrawn->refresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('reports how many selected passports the bulk publish skipped', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    resolve(Publisher::class)->withdraw($publication);

    $this->loginWithPermissions('all');

    Bus::fake();

    $this->postJson(route('admin.catalog.passports.bulk-publish'), ['indices' => [$publication->id]])
        ->assertOk()
        ->assertJsonPath('message', trans('passport::app.publications.bulk-publish-queued-skipped', ['count' => 1]));
});
