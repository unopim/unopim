<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * A seeded locale that is inactive and free of every auto disable guard.
 */
function unusedInactiveLocale(int $skip = 0): Locale
{
    return Locale::query()
        ->where('status', 0)
        ->where('code', '!=', config('app.locale'))
        ->whereDoesntHave('channel')
        ->whereDoesntHave('user')
        ->orderBy('id')
        ->skip($skip)
        ->firstOrFail();
}

/**
 * A seeded currency that is inactive and free of every auto disable guard.
 */
function unusedInactiveCurrency(int $skip = 0): Currency
{
    return Currency::query()
        ->where('status', 0)
        ->where('code', '!=', strtoupper((string) config('app.currency')))
        ->whereDoesntHave('channel')
        ->orderBy('id')
        ->skip($skip)
        ->firstOrFail();
}

function optionPayload(Locale|Currency $model): string
{
    return json_encode($model->fresh()->toArray());
}

it('lists inactive locales in the channel create page options', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();

    get(route('admin.settings.channels.create'))
        ->assertStatus(200)
        ->assertSee(optionPayload($locale));
});

it('lists inactive currencies in the channel create page options', function () {
    $this->loginAsAdmin();

    $currency = unusedInactiveCurrency();

    get(route('admin.settings.channels.create'))
        ->assertStatus(200)
        ->assertSee(optionPayload($currency));
});

it('lists inactive locales and currencies in the channel index quick create options', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $currency = unusedInactiveCurrency();

    get(route('admin.settings.channels.index'))
        ->assertStatus(200)
        ->assertSee(optionPayload($locale))
        ->assertSee(optionPayload($currency));
});

it('lists inactive locales and currencies in the channel edit page options', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $currency = unusedInactiveCurrency();
    $channel = Channel::factory()->create();

    get(route('admin.settings.channels.edit', ['id' => $channel->id]))
        ->assertStatus(200)
        ->assertSee(optionPayload($locale))
        ->assertSee(optionPayload($currency));
});

it('rejects a locale id that does not exist', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    postJson(route('admin.settings.channels.store'), [
        'code'             => 'BadLocaleChannel',
        'root_category_id' => $channel->root_category_id,
        'locales'          => (string) (Locale::max('id') + 1000),
        'currencies'       => implode(',', $channel->currencies->pluck('id')->toArray()),
    ])->assertJsonValidationErrors(['locales.0']);

    $this->assertDatabaseMissing($this->getFullTableName(Channel::class), [
        'code' => 'BadLocaleChannel',
    ]);
});

it('rejects a currency id that does not exist', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    postJson(route('admin.settings.channels.store'), [
        'code'             => 'BadCurrencyChannel',
        'root_category_id' => $channel->root_category_id,
        'locales'          => implode(',', $channel->locales->pluck('id')->toArray()),
        'currencies'       => (string) (Currency::max('id') + 1000),
    ])->assertJsonValidationErrors(['currencies.0']);

    $this->assertDatabaseMissing($this->getFullTableName(Channel::class), [
        'code' => 'BadCurrencyChannel',
    ]);
});

it('validates a scalar id payload instead of passing it through to the pivot sync', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    putJson(route('admin.settings.channels.update', ['id' => $channel->id]), [
        'root_category_id' => $channel->root_category_id,
        'locales'          => Locale::max('id') + 1000,
        'currencies'       => Currency::max('id') + 1000,
    ])->assertJsonValidationErrors(['locales.0', 'currencies.0']);

    expect($channel->locales()->count())->toBeGreaterThan(0)
        ->and($channel->currencies()->count())->toBeGreaterThan(0);
});

it('activates an inactive locale and currency attached while creating a channel', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $currency = unusedInactiveCurrency();
    $rootCategoryId = Channel::factory()->create()->root_category_id;

    $response = postJson(route('admin.settings.channels.store'), [
        'code'             => 'ActivationChannel',
        'root_category_id' => $rootCategoryId,
        'locales'          => (string) $locale->id,
        'currencies'       => (string) $currency->id,
    ]);

    $channel = Channel::where('code', 'ActivationChannel')->firstOrFail();

    $response->assertStatus(302)
        ->assertRedirect(route('admin.settings.channels.edit', $channel->id))
        ->assertSessionHas('success', trans('admin::app.settings.channels.create.create-success'));

    expect($locale->fresh()->status)->toBe(1)
        ->and($currency->fresh()->status)->toBe(1);
});

it('deactivates a locale and currency detached while updating a channel', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $currency = unusedInactiveCurrency();
    $keptLocale = Locale::where('code', config('app.locale'))->firstOrFail();
    $keptCurrency = Currency::where('code', strtoupper((string) config('app.currency')))->firstOrFail();

    $channel = Channel::factory()->create();
    $channel->locales()->sync([$keptLocale->id, $locale->id]);
    $channel->currencies()->sync([$keptCurrency->id, $currency->id]);

    $locale->update(['status' => 1]);
    $currency->update(['status' => 1]);

    $response = putJson(route('admin.settings.channels.update', ['id' => $channel->id]), [
        'root_category_id' => $channel->root_category_id,
        'locales'          => (string) $keptLocale->id,
        'currencies'       => (string) $keptCurrency->id,
    ]);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.settings.channels.edit', $channel->id))
        ->assertSessionHas('success', trans('admin::app.settings.channels.edit.update-success'));

    expect($locale->fresh()->status)->toBe(0)
        ->and($currency->fresh()->status)->toBe(0)
        ->and($keptLocale->fresh()->status)->toBe(1)
        ->and($keptCurrency->fresh()->status)->toBe(1);
});

it('keeps a detached locale active when another channel still uses it', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $keptLocale = Locale::where('code', config('app.locale'))->firstOrFail();

    $channel = Channel::factory()->create();
    $otherChannel = Channel::factory()->create();

    $channel->locales()->sync([$keptLocale->id, $locale->id]);
    $otherChannel->locales()->sync([$locale->id]);
    $locale->update(['status' => 1]);

    putJson(route('admin.settings.channels.update', ['id' => $channel->id]), [
        'root_category_id' => $channel->root_category_id,
        'locales'          => (string) $keptLocale->id,
        'currencies'       => implode(',', $channel->currencies->pluck('id')->toArray()),
    ])->assertStatus(302);

    expect($locale->fresh()->status)->toBe(1);
});

it('keeps a detached locale active when an admin still uses it as ui locale', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $keptLocale = Locale::where('code', config('app.locale'))->firstOrFail();

    $channel = Channel::factory()->create();
    $channel->locales()->sync([$keptLocale->id, $locale->id]);
    $locale->update(['status' => 1]);

    Admin::factory()->create(['ui_locale_id' => $locale->id]);

    putJson(route('admin.settings.channels.update', ['id' => $channel->id]), [
        'root_category_id' => $channel->root_category_id,
        'locales'          => (string) $keptLocale->id,
        'currencies'       => implode(',', $channel->currencies->pluck('id')->toArray()),
    ])->assertStatus(302);

    expect($locale->fresh()->status)->toBe(1);
});

it('never deactivates the application default locale and currency', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $currency = unusedInactiveCurrency();
    $defaultLocale = Locale::where('code', config('app.locale'))->firstOrFail();
    $defaultCurrency = Currency::where('code', strtoupper((string) config('app.currency')))->firstOrFail();

    $channel = Channel::factory()->create();
    $channel->locales()->sync([$defaultLocale->id]);
    $channel->currencies()->sync([$defaultCurrency->id]);

    // Leave the channel under test as the sole holder so only the protected code guard can apply.
    Channel::query()->where('id', '!=', $channel->id)->get()->each(function (Channel $other) use ($locale, $currency): void {
        $other->locales()->sync([$locale->id]);
        $other->currencies()->sync([$currency->id]);
    });

    Admin::query()->update(['ui_locale_id' => $locale->id]);

    putJson(route('admin.settings.channels.update', ['id' => $channel->id]), [
        'root_category_id' => $channel->root_category_id,
        'locales'          => (string) $locale->id,
        'currencies'       => (string) $currency->id,
    ])->assertStatus(302);

    expect($defaultLocale->fresh()->status)->toBe(1)
        ->and($defaultCurrency->fresh()->status)->toBe(1)
        ->and($locale->fresh()->status)->toBe(1)
        ->and($currency->fresh()->status)->toBe(1);
});

it('deactivates the locales and currencies of a deleted channel', function () {
    $this->loginAsAdmin();

    $locale = unusedInactiveLocale();
    $currency = unusedInactiveCurrency();

    $channel = Channel::factory()->create();
    $channel->locales()->sync([$locale->id]);
    $channel->currencies()->sync([$currency->id]);

    $locale->update(['status' => 1]);
    $currency->update(['status' => 1]);

    $this->delete(route('admin.settings.channels.delete', ['id' => $channel->id]))
        ->assertStatus(200);

    expect($locale->fresh()->status)->toBe(0)
        ->and($currency->fresh()->status)->toBe(0);
});
