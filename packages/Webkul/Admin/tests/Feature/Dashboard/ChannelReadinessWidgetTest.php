<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Category\Models\Category;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Models\ProductProxy;

function scoreProductIn(Channel $channel, Locale $locale, int $score): void
{
    $product = ProductProxy::factory()->create([
        'attribute_family_id' => AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id,
    ]);

    ProductCompletenessScore::create([
        'product_id'    => $product->id,
        'channel_id'    => $channel->id,
        'locale_id'     => $locale->id,
        'score'         => $score,
        'missing_count' => 0,
    ]);
}

function channelWithLocales(string $code, int $localeCount = 1): Channel
{
    $locales = Locale::query()->orderBy('id')->limit($localeCount)->get();

    $channel = app(ChannelRepository::class)->create([
        'code'             => $code,
        'root_category_id' => Category::whereIsRoot()->first()->id,
        'locales'          => $locales->pluck('id')->all(),
        'currencies'       => Currency::where('status', 1)->limit(1)->pluck('id')->all(),
    ]);

    return $channel->load('locales');
}

function readinessFor(string $channelCode, ?string $localeCode = null): ?array
{
    Cache::forget('dashboard.channel_readiness_by_locale');

    request()->merge(['locale' => $localeCode]);

    return collect(app(Dashboard::class)->getChannelReadiness())->firstWhere('channel', $channelCode);
}

beforeEach(function (): void {
    $this->loginAsAdmin();
});

it('counts readiness in the requested locale only, not across every locale of the channel', function (): void {
    $channel = channelWithLocales('multi-locale', 2);

    [$ready, $incomplete] = $channel->locales->values()->all();

    scoreProductIn($channel, $ready, 95);
    scoreProductIn($channel, $incomplete, 20);

    expect(readinessFor('multi-locale', $ready->code))
        ->toMatchArray(['locale' => $ready->code, 'total' => 1, 'ready' => 1]);

    expect(readinessFor('multi-locale', $incomplete->code))
        ->toMatchArray(['locale' => $incomplete->code, 'total' => 1, 'ready' => 0]);
})->skip(fn (): bool => Locale::query()->count() < 2, 'needs at least two locales');

it('reports the locale the drill-down link has to filter by', function (): void {
    $channel = channelWithLocales('reports-locale');

    $locale = $channel->locales->first();

    scoreProductIn($channel, $locale, 10);

    expect(readinessFor('reports-locale', $locale->code))
        ->toMatchArray(['locale' => $locale->code, 'total' => 1, 'ready' => 0]);
});

it('falls back to a locale the channel carries when the requested one is foreign to it', function (): void {
    $channel = channelWithLocales('foreign-locale');

    $locale = $channel->locales->first();

    scoreProductIn($channel, $locale, 10);

    $foreign = Locale::query()->whereKeyNot($locale->id)->first();

    expect(readinessFor('foreign-locale', $foreign->code))
        ->toHaveKey('locale', core()->getLocaleCodeInChannel($channel));
})->skip(fn (): bool => Locale::query()->count() < 2, 'needs at least two locales');

it('serves the channel readiness payload from the dashboard stats endpoint', function (): void {
    Cache::forget('dashboard.channel_readiness_by_locale');

    $this->getJson(route('admin.dashboard.stats', ['type' => 'channel-readiness']))
        ->assertOk()
        ->assertJsonStructure(['statistics']);
});

it('renders the drill-down as a plain link when Elasticsearch is disabled', function (): void {
    config(['elasticsearch.enabled' => false]);

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('esEnabled: false', false);
});

it('hides the drill-down link when Elasticsearch is enabled, since the list would use the average', function (): void {
    config(['elasticsearch.enabled' => true]);

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('esEnabled: true', false);
});
