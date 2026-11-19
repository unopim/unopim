<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Services\ChannelCompletenessColumn;

function drilldownProduct(Channel $channel, int $locale_id, int $channelScore, int $avgScore): Product
{
    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $product = ProductProxy::factory()->create([
        'attribute_family_id' => $familyId,
    ]);

    DB::table('products')->where('id', $product->id)->update([
        'avg_completeness_score' => $avgScore,
    ]);

    ProductCompletenessScore::create([
        'product_id'    => $product->id,
        'channel_id'    => $channel->id,
        'locale_id'     => $locale_id,
        'score'         => $channelScore,
        'missing_count' => 0,
    ]);

    return $product;
}

beforeEach(function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => false]);
});

it('filters by the requested channel\'s own completeness score, not the cross-channel average', function () {
    $channel = Channel::factory()->create(['code' => 'erp']);
    $locale = $channel->locales()->first();

    $incompleteForChannel = drilldownProduct($channel, $locale->id, channelScore: 50, avgScore: 100);
    $readyForChannel = drilldownProduct($channel, $locale->id, channelScore: 90, avgScore: 10);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), [
            'channel'        => $channel->code,
            'locale'         => $locale->code,
            'pagination'     => ['page' => 1, 'per_page' => 50],
            'managedColumns' => ['sku', 'completeness'],
            'filters'        => [
                'indices'      => [$incompleteForChannel->id, $readyForChannel->id],
                'completeness' => [['operator' => 'lt', 'value' => 80]],
            ],
        ])->assertOk();

    $skus = collect($response->json('records'))->pluck('sku')->all();

    expect($skus)->toContain($incompleteForChannel->sku);
    expect($skus)->not->toContain($readyForChannel->sku);
});

it('keeps using the cross-channel average when no channel is requested', function () {
    $channel = Channel::factory()->create(['code' => 'print']);
    $locale = $channel->locales()->first();

    $product = drilldownProduct($channel, $locale->id, channelScore: 95, avgScore: 40);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), [
            'pagination'     => ['page' => 1, 'per_page' => 50],
            'managedColumns' => ['sku', 'completeness'],
            'filters'        => [
                'indices'      => [$product->id],
                'completeness' => [['operator' => 'lt', 'value' => 80]],
            ],
        ])->assertOk();

    expect(collect($response->json('records'))->pluck('sku')->all())
        ->toContain($product->sku);
});

it('drops the per-channel scoping while Elasticsearch is enabled, so the displayed column and the filtered rows stay on the average it indexes', function () {
    $channel = Channel::factory()->create(['code' => 'marketplace']);
    $locale = $channel->locales()->first();

    request()->merge(['channel' => $channel->code, 'locale' => $locale->code]);

    expect(app(ChannelCompletenessColumn::class)->resolve())
        ->toHaveKeys(['channel', 'locale']);

    config(['elasticsearch.enabled' => true]);

    app()->forgetScopedInstances();

    expect(app(ChannelCompletenessColumn::class)->resolve())->toBeNull();
});
