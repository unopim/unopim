<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;

/**
 * The exported link is rebuilt from the current base url — the stored alias marks
 * GTIN ownership only, so a stale host can never reach a printed carrier.
 */
it('exports gtin, a freshly built gs1 link and the public url for the print hand-off', function (): void {
    $publication = Publication::factory()->create([
        'gtin'             => '04006381333931',
        'alias_identifier' => 'https://stale.example.test/01/04006381333931',
    ]);

    $grid = resolve(PublicationDataGrid::class);
    $grid->setQueryBuilder();

    $rows = collect($grid->getExportableData())
        ->filter(fn ($row): bool => ((array) $row)['gtin'] === '04006381333931')
        ->values();

    expect($rows)->toHaveCount(1);

    $row = (array) $rows[0];

    expect($row['gtin'])->toBe('04006381333931')
        ->and($row['gs1_link'])->toBe(url('/01/04006381333931'))
        ->and($row['public_url'])->toContain($publication->uuid);
});

it('lists publications for an authorised admin', function (): void {
    $version = $this->publishedPassportFixture();

    $this->enablePassportPublishing($version->publication->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.passports.index'))
        ->assertOk();

    // getJson() omits X-Requested-With, which request()->ajax() needs to reach the DataGrid::toJson() branch.
    $this->getJson(route('admin.catalog.passports.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertSee($version->publication->uuid);
});

it('labels the publication bulk action as republish', function (): void {
    $this->loginWithPermissions('all');

    $grid = resolve(PublicationDataGrid::class);
    $grid->prepareMassActions();

    $massActionTitles = collect($grid->getMassActions())
        ->pluck('title');

    expect($massActionTitles)->toContain('Republish selected');
});

it('rejects withdrawal without the withdraw permission', function (): void {
    $version = $this->publishedPassportFixture();

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->post(route('admin.catalog.passports.withdraw', $version->publication))
        ->assertForbidden();
});

it('publishes every requested locale in a single job dispatch, not one per locale', function (): void {
    Bus::fake();

    [$product, $context] = $this->productWithSecretAndDppAttributes();
    $otherLocale = Locale::factory()->create();
    $context->channel->locales()->attach($otherLocale);

    $this->enablePassportPublishing($context->channel->code);

    foreach ([$context->locale->id, $otherLocale->id] as $localeId) {
        ProductCompletenessScore::query()->create([
            'product_id' => $product->id, 'channel_id' => $context->channel->id,
            'locale_id'  => $localeId, 'score' => 100, 'missing_count' => 0,
        ]);
    }

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.publish', $product), [
        'channel_id' => $context->channel->id,
        'locale_ids' => [$context->locale->id, $otherLocale->id],
    ])->assertOk();

    Bus::assertDispatchedTimes(PublishPassportForProductChannelJob::class, 1);
});

it('mass publishes selected products, one job dispatch per product', function (): void {
    Bus::fake();

    [$productA, $context] = $this->productWithSecretAndDppAttributes();
    $productB = ProductProxy::factory()->create([
        'attribute_family_id' => $productA->attribute_family_id,
    ]);

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.mass_publish'), [
        'channel' => $context->channel->code,
        'indices' => [$productA->id, $productB->id],
    ])->assertOk();

    Bus::assertDispatchedTimes(PublishPassportForProductChannelJob::class, 2);
});

it('rejects mass publish without the publish permission', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->postJson(route('admin.catalog.passports.mass_publish'), [
        'channel' => $context->channel->code,
        'indices' => [$product->id],
    ])->assertForbidden();
});
