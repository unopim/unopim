<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;

it('persists a catalog locale and default channel on an admin', function () {
    $locale = Locale::where('status', 1)->firstOrFail();
    $channel = Channel::firstOrFail();

    $admin = Admin::first();

    $admin->update([
        'catalog_locale_id'  => $locale->id,
        'default_channel_id' => $channel->id,
    ]);

    $admin->refresh();

    expect($admin->catalog_locale_id)->toBe($locale->id);
    expect($admin->catalogLocale->code)->toBe($locale->code);
    expect($admin->defaultChannel->code)->toBe($channel->code);
});

it('updates the account catalog scope', function () {
    $this->loginAsAdmin();

    $admin = auth()->guard('admin')->user();
    $locale = Locale::where('status', 1)->firstOrFail();
    $channel = Channel::firstOrFail();

    $this->put(route('admin.account.update'), [
        'name'               => $admin->name,
        'email'              => $admin->email,
        'password'           => '',
        'current_password'   => 'password',
        'timezone'           => 'UTC',
        'ui_locale_id'       => $admin->ui_locale_id,
        'catalog_locale_id'  => $locale->id,
        'default_channel_id' => $channel->id,
    ])->assertRedirect();

    $admin->refresh();

    expect($admin->catalog_locale_id)->toBe($locale->id);
    expect($admin->default_channel_id)->toBe($channel->id);
});

it('rejects an inactive catalog locale on the account form', function () {
    $this->loginAsAdmin();

    $admin = auth()->guard('admin')->user();
    $inactive = Locale::where('status', 0)->firstOrFail();

    $this->put(route('admin.account.update'), [
        'name'              => $admin->name,
        'email'             => $admin->email,
        'current_password'  => 'password',
        'timezone'          => 'UTC',
        'ui_locale_id'      => $admin->ui_locale_id,
        'catalog_locale_id' => $inactive->id,
    ])->assertInvalid('catalog_locale_id');
});
