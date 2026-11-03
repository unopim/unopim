<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Models\ProductProxy;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->admin = $this->loginAsAdmin();

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

/**
 * Fetches the channel-readiness payload keyed by channel code.
 */
function fetchReadinessByCode(): Collection
{
    $response = getJson(route('admin.dashboard.stats', ['type' => 'channel-readiness']));

    $response->assertOk();

    return collect($response->json('statistics'))->keyBy('channel');
}

it('labels each channel with its translated name instead of the raw code', function () {
    $channel = Channel::factory()->create(['code' => 'erp']);

    DB::table('channel_translations')->updateOrInsert(
        ['channel_id' => $channel->id, 'locale' => 'en_US'],
        ['name' => 'ERP System']
    );

    seedReadinessRow($channel);

    expect(fetchReadinessByCode()['erp']['label'])->toBe('ERP System');
});

it('falls back to [code] when the channel has no translation for the current locale', function () {
    $channel = Channel::factory()->create(['code' => 'rawcode']);

    DB::table('channel_translations')->where('channel_id', $channel->id)->delete();

    seedReadinessRow($channel);

    expect(fetchReadinessByCode()['rawcode']['label'])->toBe('[rawcode]');
});

it('resolves labels for the requesting admin locale even when the cache was warmed in another locale', function () {
    $channel = Channel::factory()->create(['code' => 'erp']);

    DB::table('channel_translations')->updateOrInsert(
        ['channel_id' => $channel->id, 'locale' => 'en_US'],
        ['name' => 'ERP System']
    );

    DB::table('channel_translations')->updateOrInsert(
        ['channel_id' => $channel->id, 'locale' => 'de_DE'],
        ['name' => 'ERP-System DE']
    );

    seedReadinessRow($channel);

    expect(fetchReadinessByCode()['erp']['label'])->toBe('ERP System')
        ->and(Cache::has('dashboard.channel_readiness'))->toBeTrue();

    $germanLocale = Locale::where('code', 'de_DE')->first()
        ?? Locale::factory()->create(['code' => 'de_DE']);

    $this->admin->update(['ui_locale_id' => $germanLocale->id]);

    expect(fetchReadinessByCode()['erp']['label'])->toBe('ERP-System DE');
});

it('shows a renamed channel without waiting for the readiness cache to expire', function () {
    $channel = Channel::factory()->create(['code' => 'erp']);

    DB::table('channel_translations')->updateOrInsert(
        ['channel_id' => $channel->id, 'locale' => 'en_US'],
        ['name' => 'ERP System']
    );

    seedReadinessRow($channel);

    expect(fetchReadinessByCode()['erp']['label'])->toBe('ERP System');

    app(ChannelRepository::class)->update([
        'en_US'      => ['name' => 'ERP Renamed'],
        'locales'    => $channel->locales()->pluck('locales.id')->all(),
        'currencies' => $channel->currencies()->pluck('currencies.id')->all(),
    ], $channel->id);

    expect(fetchReadinessByCode()['erp']['label'])->toBe('ERP Renamed');
});
