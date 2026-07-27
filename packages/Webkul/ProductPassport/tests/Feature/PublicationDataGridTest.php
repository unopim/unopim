<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;

it('lists publications for an authorised admin', function (): void {
    $version = $this->publishedPassportFixture();

    $this->enablePassportPublishing($version->publication->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.passports.index'))
        ->assertOk();

    // `request()->ajax()` (what the controller branches on) checks the
    // X-Requested-With header specifically — Pest's getJson() sets
    // Accept/Content-Type but not that header, so it must be added
    // explicitly to reach the DataGrid::toJson() branch instead of the
    // full HTML view.
    $this->getJson(route('admin.catalog.passports.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertSee($version->publication->uuid);
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
