<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('stores a new user with a catalog scope', function () {
    $this->loginAsAdmin();

    $locale = Locale::where('status', 1)->firstOrFail();
    $channel = Channel::firstOrFail();
    $role = Role::firstOrFail();

    $this->postJson(route('admin.settings.users.store'), [
        'name'                  => 'Scoped User',
        'email'                 => 'scoped.user@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => $role->id,
        'ui_locale_id'          => Locale::where('code', 'en_US')->value('id'),
        'catalog_locale_id'     => $locale->id,
        'default_channel_id'    => $channel->id,
        'timezone'              => 'UTC',
        'status'                => 1,
    ])->assertOk();

    $user = Admin::where('email', 'scoped.user@example.com')->firstOrFail();

    expect($user->catalog_locale_id)->toBe($locale->id);
    expect($user->default_channel_id)->toBe($channel->id);
});

it('rejects an inactive catalog locale when creating a user', function () {
    $this->loginAsAdmin();

    $inactive = Locale::where('status', 0)->firstOrFail();
    $role = Role::firstOrFail();

    $this->postJson(route('admin.settings.users.store'), [
        'name'                  => 'Bad Scope User',
        'email'                 => 'bad.scope@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => $role->id,
        'ui_locale_id'          => Locale::where('code', 'en_US')->value('id'),
        'catalog_locale_id'     => $inactive->id,
        'timezone'              => 'UTC',
        'status'                => 1,
    ])->assertStatus(422)
        ->assertJsonValidationErrors('catalog_locale_id');
});
