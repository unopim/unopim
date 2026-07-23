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

it('resolves the default locale from the default channel rather than a hardcoded en_US', function () {
    $channel = core()->getDefaultChannel();

    expect(core()->getDefaultLocaleCodeFromDefaultChannel())
        ->toBe($channel->locales->first()->code);
});

it('falls back to the channel first locale when the requested locale is not in the channel', function () {
    request()->merge(['locale' => 'zz_ZZ']);

    $channel = core()->getRequestedChannel();

    expect(core()->getRequestedLocaleCodeInRequestedChannel())
        ->toBe($channel->locales->first()->code);
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
