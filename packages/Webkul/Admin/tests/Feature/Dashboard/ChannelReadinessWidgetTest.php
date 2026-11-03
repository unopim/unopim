<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
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
    $channel = Channel::factory()->create(['code' => $code]);

    $channel->locales()->sync(Locale::query()->orderBy('id')->limit($localeCount)->pluck('id'));

    return $channel->load('locales');
}

function readinessFor(string $channelCode, ?string $localeCode = null): ?array
{
    Cache::forget('dashboard.channel_readiness');

    request()->merge(['locale' => $localeCode]);

    return collect(app(Dashboard::class)->getChannelReadiness())->firstWhere('channel', $channelCode);
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('counts readiness in the requested locale only, not across every locale of the channel', function () {
    $channel = channelWithLocales('multi-locale', 2);

    [$ready, $incomplete] = $channel->locales->values()->all();

    scoreProductIn($channel, $ready, 95);
    scoreProductIn($channel, $incomplete, 20);

    expect(readinessFor('multi-locale', $ready->code))
        ->toMatchArray(['locale' => $ready->code, 'total' => 1, 'ready' => 1]);

    expect(readinessFor('multi-locale', $incomplete->code))
        ->toMatchArray(['locale' => $incomplete->code, 'total' => 1, 'ready' => 0]);
})->skip(fn () => Locale::query()->count() < 2, 'needs at least two locales');

it('reports the locale the drill-down link has to filter by', function () {
    $channel = channelWithLocales('reports-locale');

    $locale = $channel->locales->first();

    scoreProductIn($channel, $locale, 10);

    expect(readinessFor('reports-locale', $locale->code))
        ->toMatchArray(['locale' => $locale->code, 'total' => 1, 'ready' => 0]);
});

it('falls back to a locale the channel carries when the requested one is foreign to it', function () {
    $channel = channelWithLocales('foreign-locale');

    $locale = $channel->locales->first();

    scoreProductIn($channel, $locale, 10);

    $foreign = Locale::query()->whereKeyNot($locale->id)->first();

    expect(readinessFor('foreign-locale', $foreign->code))
        ->toHaveKey('locale', core()->getLocaleCodeInChannel($channel));
})->skip(fn () => Locale::query()->count() < 2, 'needs at least two locales');

it('serves the channel readiness payload from the dashboard stats endpoint', function () {
    Cache::forget('dashboard.channel_readiness');

    $this->getJson(route('admin.dashboard.stats', ['type' => 'channel-readiness']))
        ->assertOk()
        ->assertJsonStructure(['statistics']);
});
