<?php

use Webkul\Core\CatalogScope;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;

function scope(): CatalogScope
{
    return app()->make(CatalogScope::class);
}

it('prefers an explicit request locale over everything else', function () {
    request()->merge(['locale' => 'fr_FR']);

    expect(scope()->localeCode())->toBe('fr_FR');
});

it('falls back to the authenticated admin catalog locale, not the ui locale', function () {
    $french = Locale::where('code', 'fr_FR')->firstOrFail();
    $french->update(['status' => 1]);

    $admin = Admin::first();
    $admin->update([
        'ui_locale_id'      => Locale::where('code', 'en_US')->value('id'),
        'catalog_locale_id' => $french->id,
    ]);

    auth()->guard('admin')->login($admin);

    app()->setLocale('en_US');

    expect(scope()->localeCode())->toBe('fr_FR');
});

it('ignores a catalog locale that is no longer active', function () {
    $french = Locale::where('code', 'fr_FR')->firstOrFail();
    $french->update(['status' => 0]);

    $admin = Admin::first();
    $admin->update(['catalog_locale_id' => $french->id]);

    auth()->guard('admin')->login($admin);

    expect(scope()->localeCode())->not->toBe('fr_FR');
});

it('resolves without an authenticated admin and never touches auth', function () {
    auth()->guard('admin')->logout();

    expect(scope()->localeCode())->toBeString()->not->toBeEmpty();
    expect(scope()->channelCode())->toBeString()->not->toBeEmpty();
});

it('does not leak one admin scope into the next resolution', function () {
    $french = Locale::where('code', 'fr_FR')->firstOrFail();
    $french->update(['status' => 1]);

    $first = Admin::first();
    $first->update(['catalog_locale_id' => $french->id]);

    auth()->guard('admin')->login($first);

    expect(scope()->localeCode())->toBe('fr_FR');

    /**
     * Simulate the next Octane request: the container's scoped instances are flushed, the previous
     * admin is gone. A singleton-backed scope would still answer fr_FR here.
     */
    auth()->guard('admin')->logout();
    app()->forgetScopedInstances();

    expect(scope()->localeCode())->not->toBe('fr_FR');
});

it('prefers the admin default channel over the config default', function () {
    $channel = Channel::firstOrFail();

    $admin = Admin::first();
    $admin->update(['default_channel_id' => $channel->id]);

    auth()->guard('admin')->login($admin);

    expect(scope()->channelCode())->toBe($channel->code);
});

it('makes getRequestedLocaleCode fall back to the catalog scope, not the ui locale', function () {
    $french = Locale::where('code', 'fr_FR')->firstOrFail();
    $french->update(['status' => 1]);

    $admin = Admin::first();
    $admin->update(['catalog_locale_id' => $french->id]);

    auth()->guard('admin')->login($admin);

    app()->setLocale('en_US');

    expect(core()->getRequestedLocaleCode())->toBe('fr_FR');
});

it('still honours an explicit locale parameter', function () {
    request()->merge(['locale' => 'de_DE']);

    expect(core()->getRequestedLocaleCode())->toBe('de_DE');
});

function attachLocales(array $codes): void
{
    $channel = core()->getDefaultChannel();

    $channel->locales()->sync(
        Locale::whereIn('code', $codes)->pluck('id')->all()
    );

    $channel->unsetRelation('locales');
}

it('resolves the configured application locale when the default channel carries it', function () {
    config(['app.locale' => 'en_US']);

    attachLocales(['de_DE', 'en_US', 'fr_FR']);

    expect(core()->getDefaultLocaleCodeFromDefaultChannel())->toBe('en_US');
});

it('does not fall back to a hardcoded en_US when the channel does not carry English', function () {
    config(['app.locale' => 'en_US']);

    attachLocales(['de_DE', 'fr_FR']);

    expect(core()->getDefaultLocaleCodeFromDefaultChannel())->toBe('de_DE');
});

it('answers the same regardless of the order the locales were attached', function () {
    config(['app.locale' => 'en_US']);

    $ids = Locale::whereIn('code', ['de_DE', 'en_US', 'fr_FR'])->pluck('id')->all();

    $channel = core()->getDefaultChannel();

    $channel->locales()->sync($ids);
    $channel->unsetRelation('locales');
    $attachedInOrder = core()->getDefaultLocaleCodeFromDefaultChannel();

    $channel->locales()->sync(array_reverse($ids));
    $channel->unsetRelation('locales');
    $attachedReversed = core()->getDefaultLocaleCodeFromDefaultChannel();

    expect($attachedInOrder)->toBe('en_US')->toBe($attachedReversed);
});

it('falls back to the configured locale when the channel carries none', function () {
    config(['app.locale' => 'en_US']);

    $channel = core()->getDefaultChannel();
    $channel->locales()->detach();
    $channel->unsetRelation('locales');

    expect(core()->getDefaultLocaleCodeFromDefaultChannel())->toBe('en_US');
});

it('resolves the catalog scope locale to the configured locale when no admin locale is set', function () {
    config(['app.locale' => 'en_US']);

    auth()->guard('admin')->logout();

    attachLocales(['de_DE', 'en_US', 'fr_FR']);

    expect(scope()->localeCode())->toBe('en_US');
});

it('falls back to a channel locale when the requested locale is not in the channel', function () {
    config(['app.locale' => 'en_US']);

    attachLocales(['de_DE', 'en_US', 'fr_FR']);

    request()->merge(['locale' => 'zz_ZZ']);

    expect(core()->getRequestedLocaleCodeInRequestedChannel())->toBe('en_US');
});

it('lets an explicitly set current channel outrank the admin default channel', function () {
    $defaultChannel = Channel::factory()->create();
    $workingChannel = Channel::factory()->create();

    $admin = Admin::first();
    $admin->update(['default_channel_id' => $defaultChannel->id]);

    auth()->guard('admin')->login($admin);

    core()->setCurrentChannel($workingChannel);

    expect(core()->getRequestedChannelCode())->toBe($workingChannel->code);
});

it('does not let a boot-time resolution freeze the answer for the rest of the request', function () {
    auth()->guard('admin')->logout();

    $bootTimeLocaleCode = scope()->localeCode();

    $french = Locale::where('code', 'fr_FR')->firstOrFail();
    $french->update(['status' => 1]);

    $admin = Admin::first();
    $admin->update(['catalog_locale_id' => $french->id]);

    auth()->guard('admin')->login($admin);

    expect(scope()->localeCode())
        ->toBe('fr_FR')
        ->not->toBe($bootTimeLocaleCode);
});

it('prefers the configured application locale over the channel locale order when no catalog locale is set', function () {
    $admin = Admin::first();
    $admin->update(['catalog_locale_id' => null]);

    auth()->guard('admin')->login($admin);

    $channel = core()->getDefaultChannel();

    $locales = Locale::whereIn('code', ['en_US', 'fr_FR'])->get();

    expect($locales)->toHaveCount(2);

    $locales->each(fn ($locale) => $locale->update(['status' => 1]));

    $channel->locales()->syncWithoutDetaching($locales->pluck('id')->all());

    $channel->unsetRelation('locales');

    $channelFirstLocaleCode = $channel->locales->first()->code;

    $appLocale = $channel->locales->firstWhere('code', '!=', $channelFirstLocaleCode);

    config(['app.locale' => $appLocale->code]);

    expect(scope()->localeCode())
        ->toBe($appLocale->code)
        ->not->toBe($channelFirstLocaleCode);
});

it('keeps the channel first locale when the application locale is not attached to the channel', function () {
    $admin = Admin::first();
    $admin->update(['catalog_locale_id' => null]);

    auth()->guard('admin')->login($admin);

    $channel = core()->getDefaultChannel();

    config(['app.locale' => 'zz_ZZ']);

    expect(scope()->localeCode())->toBe($channel->locales->first()->code);
});
