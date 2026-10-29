<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;

/*
 * The dashboard's "channel readiness" widget drill-down links to the product
 * list scoped to one channel (?channel=<code>) and filtered to the products
 * still below the readiness threshold. For that to show the right work
 * queue, the grid's "completeness" column has to reflect that channel's own
 * score (product_completeness) instead of the product's cross-channel
 * average once a channel is explicitly requested.
 */

function drilldownProduct(Channel $channel, int $locale_id, int $channelScore, int $avgScore): Product
{
    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $product = ProductProxy::factory()->create([
        'attribute_family_id' => $familyId,
    ]);

    // Not mass-assignable (by design — the score is computed by a job), so
    // write it directly to simulate "already scored by the completeness
    // job"; forceFill()->save() doesn't persist it here for the same reason.
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
});

it('filters by the requested channel\'s own completeness score, not the cross-channel average', function () {
    $channel = Channel::factory()->create(['code' => 'erp']);
    $locale = $channel->locales()->first();

    // Incomplete for this channel (50 < 80) even though its overall average
    // across every channel happens to be high.
    $incompleteForChannel = drilldownProduct($channel, $locale->id, channelScore: 50, avgScore: 100);

    // Ready for this channel (90 >= 80) even though its overall average
    // happens to be low — proves the column isn't silently reading the
    // average when a channel is explicitly requested.
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

    // Incomplete on average, but complete for this one channel — without an
    // explicit ?channel=, the grid's existing (pre-drill-down) behaviour of
    // filtering by the average must be unchanged.
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
