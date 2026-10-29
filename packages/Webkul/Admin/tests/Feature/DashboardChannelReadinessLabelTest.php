<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\ProductProxy;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->loginAsAdmin();

    Cache::forget('dashboard.channel_readiness');
});

/**
 * Seeds one completeness row for the given channel so it appears in the
 * channel-readiness aggregate.
 */
function seedReadinessRow(Channel $channel): void
{
    $locale = $channel->locales()->first();

    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $product = ProductProxy::factory()->create(['attribute_family_id' => $familyId]);

    ProductCompletenessScore::create([
        'product_id'    => $product->id,
        'channel_id'    => $channel->id,
        'locale_id'     => $locale->id,
        'score'         => 90,
        'missing_count' => 0,
    ]);
}

it('labels each channel with its translated name instead of the raw code', function () {
    $translated = Channel::factory()->create(['code' => 'erp']);

    DB::table('channel_translations')->updateOrInsert(
        ['channel_id' => $translated->id, 'locale' => 'en_US'],
        ['name' => 'ERP System']
    );

    $untranslated = Channel::factory()->create(['code' => 'rawcode']);

    DB::table('channel_translations')->where('channel_id', $untranslated->id)->delete();

    seedReadinessRow($translated);
    seedReadinessRow($untranslated);

    $response = getJson(route('admin.dashboard.stats', ['type' => 'channel-readiness']));

    $response->assertOk();

    $byCode = collect($response->json('statistics'))->keyBy('channel');

    expect($byCode['erp']['label'])->toBe('ERP System');

    // No translation exists for this locale, so the label falls back to the
    // existing `[code]` convention (see ChannelRepository::getChannelAsOptions())
    // instead of rendering a blank value.
    expect($byCode['rawcode']['label'])->toBe('[rawcode]');
});
