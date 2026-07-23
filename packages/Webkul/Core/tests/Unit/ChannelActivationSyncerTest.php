<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Services\ChannelActivationSyncer;
use Webkul\User\Models\Admin;

function channelSyncer(): ChannelActivationSyncer
{
    return app(ChannelActivationSyncer::class);
}

function channelRepository(): ChannelRepository
{
    return app(ChannelRepository::class);
}

function makeLocale(int $status): Locale
{
    do {
        $code = 'zz_'.strtoupper(fake()->unique()->lexify('??'));
    } while ($code === config('app.locale') || Locale::query()->where('code', $code)->exists());

    return Locale::factory()->create(['code' => $code, 'status' => $status]);
}

/**
 * The faker currency codes overlap the real ISO list, so a fixture could randomly land on the
 * application default currency and be legitimately protected from deactivation.
 */
function makeCurrency(int $status): Currency
{
    do {
        $code = 'X'.strtoupper(fake()->unique()->lexify('??'));
    } while (strcasecmp($code, (string) config('app.currency')) === 0);

    return Currency::factory()->create([
        'code'    => $code,
        'status'  => $status,
        'symbol'  => '¤',
        'decimal' => 2,
    ]);
}

/**
 * Build a channel with an explicitly controlled locale and currency set, bypassing the
 * repository so the syncer under test is never triggered by the arrangement itself.
 */
function makeChannelWith(array $localeIds = [], array $currencyIds = []): Channel
{
    $channel = Channel::factory()->create();

    $channel->locales()->sync($localeIds);
    $channel->currencies()->sync($currencyIds);

    return $channel;
}

it('activates an inactive locale that gets attached', function () {
    $locale = makeLocale(0);

    channelSyncer()->syncLocales([$locale->id], []);

    expect($locale->refresh()->status)->toBe(1);
});

it('activates an inactive currency that gets attached', function () {
    $currency = makeCurrency(0);

    channelSyncer()->syncCurrencies([$currency->id], []);

    expect($currency->refresh()->status)->toBe(1);
});

it('leaves an already active locale untouched when it gets attached', function () {
    $locale = makeLocale(1);

    channelSyncer()->syncLocales([$locale->id], []);

    expect($locale->refresh()->status)->toBe(1);
});

it('deactivates a locale detached from its only channel', function () {
    $locale = makeLocale(1);

    channelSyncer()->syncLocales([], [$locale->id]);

    expect($locale->refresh()->status)->toBe(0);
});

it('deactivates a currency detached from its only channel', function () {
    $currency = makeCurrency(1);

    channelSyncer()->syncCurrencies([], [$currency->id]);

    expect($currency->refresh()->status)->toBe(0);
});

it('keeps a detached locale active while another channel still uses it', function () {
    $locale = makeLocale(1);

    makeChannelWith([$locale->id]);

    channelSyncer()->syncLocales([], [$locale->id]);

    expect($locale->refresh()->status)->toBe(1);
});

it('keeps a detached currency active while another channel still uses it', function () {
    $currency = makeCurrency(1);

    makeChannelWith([], [$currency->id]);

    channelSyncer()->syncCurrencies([], [$currency->id]);

    expect($currency->refresh()->status)->toBe(1);
});

it('keeps a detached locale active while an admin still uses it as ui locale', function () {
    $locale = makeLocale(1);

    Admin::factory()->create(['ui_locale_id' => $locale->id]);

    channelSyncer()->syncLocales([], [$locale->id]);

    expect($locale->refresh()->status)->toBe(1);
});

it('never deactivates the application default locale', function () {
    $locale = makeLocale(1);

    config(['app.locale' => $locale->code]);

    channelSyncer()->syncLocales([], [$locale->id]);

    expect($locale->refresh()->status)->toBe(1);
});

it('never deactivates the application default currency', function () {
    $currency = makeCurrency(1);

    config(['app.currency' => strtolower($currency->code)]);

    channelSyncer()->syncCurrencies([], [$currency->id]);

    expect($currency->refresh()->status)->toBe(1);
});

it('treats a locale present on both sides as attached', function () {
    $locale = makeLocale(0);

    channelSyncer()->syncLocales([$locale->id], [$locale->id]);

    expect($locale->refresh()->status)->toBe(1);
});

it('treats a currency present on both sides as attached', function () {
    $currency = makeCurrency(0);

    channelSyncer()->syncCurrencies([$currency->id], [$currency->id]);

    expect($currency->refresh()->status)->toBe(1);
});

it('runs no query when both sides are empty', function () {
    $locale = makeLocale(0);

    DB::enableQueryLog();
    DB::flushQueryLog();

    channelSyncer()->syncLocales([], []);
    channelSyncer()->syncCurrencies([], []);

    expect(DB::getQueryLog())->toBeEmpty();

    channelSyncer()->syncLocales([$locale->id], []);

    expect(DB::getQueryLog())->not->toBeEmpty();

    DB::disableQueryLog();
});

it('dispatches the activation event only for the ids that actually changed', function () {
    Event::fake(['core.locale.activation.synced']);

    $enabled = makeLocale(0);
    $untouched = makeLocale(1);

    channelSyncer()->syncLocales([$enabled->id, $untouched->id], []);

    Event::assertDispatched(
        'core.locale.activation.synced',
        fn ($event, $payload): bool => $payload['enabled'] === [$enabled->id] && $payload['disabled'] === []
    );
});

it('does not dispatch the activation event when nothing changed', function () {
    Event::fake(['core.currency.activation.synced']);

    $currency = makeCurrency(1);

    channelSyncer()->syncCurrencies([$currency->id], []);

    Event::assertNotDispatched('core.currency.activation.synced');
});

it('activates the locales and currencies attached while creating a channel', function () {
    $locale = makeLocale(0);
    $currency = makeCurrency(0);

    channelRepository()->create([
        'code'             => 'act_'.uniqid(),
        'root_category_id' => Channel::first()->root_category_id,
        'locales'          => [$locale->id],
        'currencies'       => [$currency->id],
    ]);

    expect($locale->refresh()->status)->toBe(1)
        ->and($currency->refresh()->status)->toBe(1);
});

it('flips activation for both sides while updating a channel', function () {
    $detachedLocale = makeLocale(1);
    $attachedLocale = makeLocale(0);
    $detachedCurrency = makeCurrency(1);
    $attachedCurrency = makeCurrency(0);

    $channel = makeChannelWith([$detachedLocale->id], [$detachedCurrency->id]);

    channelRepository()->update([
        'locales'    => [$attachedLocale->id],
        'currencies' => [$attachedCurrency->id],
    ], $channel->id);

    expect($detachedLocale->refresh()->status)->toBe(0)
        ->and($attachedLocale->refresh()->status)->toBe(1)
        ->and($detachedCurrency->refresh()->status)->toBe(0)
        ->and($attachedCurrency->refresh()->status)->toBe(1);
});

it('deactivates the locales and currencies orphaned by deleting a channel', function () {
    $locale = makeLocale(1);
    $currency = makeCurrency(1);

    $channel = makeChannelWith([$locale->id], [$currency->id]);

    channelRepository()->delete($channel->id);

    expect($locale->refresh()->status)->toBe(0)
        ->and($currency->refresh()->status)->toBe(0);
});

it('keeps locales and currencies active when deleting a channel that does not own them alone', function () {
    $locale = makeLocale(1);
    $currency = makeCurrency(1);

    $channel = makeChannelWith([$locale->id], [$currency->id]);

    makeChannelWith([$locale->id], [$currency->id]);

    channelRepository()->delete($channel->id);

    expect($locale->refresh()->status)->toBe(1)
        ->and($currency->refresh()->status)->toBe(1);
});
