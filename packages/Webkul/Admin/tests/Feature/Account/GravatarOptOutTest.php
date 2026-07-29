<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\DataGrids\Settings\UserDataGrid;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

describe('Gravatar opt-out persistence', function () {
    it('persists the gravatar opt-out from the account form and back', function () {
        $this->loginAsAdmin();

        $admin = auth()->guard('admin')->user();

        $payload = [
            'name'             => $admin->name,
            'email'            => $admin->email,
            'current_password' => 'password',
            'timezone'         => 'UTC',
            'ui_locale_id'     => $admin->ui_locale_id,
        ];

        $this->put(route('admin.account.update'), $payload + ['use_gravatar' => 0])->assertRedirect();
        expect($admin->fresh()->use_gravatar)->toBeFalse();

        $this->put(route('admin.account.update'), $payload + ['use_gravatar' => 1])->assertRedirect();
        expect($admin->fresh()->use_gravatar)->toBeTrue();
    });

    it('does not persist the gravatar as an uploaded image on account save', function () {
        $this->loginAsAdmin();

        $admin = auth()->guard('admin')->user();

        expect($admin->image)->toBeNull();

        $this->put(route('admin.account.update'), [
            'name'             => $admin->name,
            'email'            => $admin->email,
            'current_password' => 'password',
            'timezone'         => 'UTC',
            'ui_locale_id'     => $admin->ui_locale_id,
            'use_gravatar'     => 1,
        ])->assertRedirect();

        expect($admin->fresh()->image)->toBeNull();
    });

    it('defaults use_gravatar to true when creating a user without the field', function () {
        $this->loginAsAdmin();

        $role = Role::firstOrFail();

        $this->postJson(route('admin.settings.users.store'), [
            'name'                  => 'No Field User',
            'email'                 => 'no.field@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role_id'               => $role->id,
            'ui_locale_id'          => Locale::where('code', 'en_US')->value('id'),
            'timezone'              => 'UTC',
            'status'                => 1,
        ])->assertOk();

        expect(Admin::where('email', 'no.field@example.com')->value('use_gravatar'))->toBeTrue();
    });

    it('persists an explicit gravatar opt-out when creating a user', function () {
        $this->loginAsAdmin();

        $role = Role::firstOrFail();

        $this->postJson(route('admin.settings.users.store'), [
            'name'                  => 'Opted Out User',
            'email'                 => 'opted.out@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role_id'               => $role->id,
            'ui_locale_id'          => Locale::where('code', 'en_US')->value('id'),
            'timezone'              => 'UTC',
            'status'                => 1,
            'use_gravatar'          => 0,
        ])->assertOk();

        expect(Admin::where('email', 'opted.out@example.com')->value('use_gravatar'))->toBeFalse();
    });

    it('omits the gravatar in the user datagrid for opted-out users', function () {
        $this->loginAsAdmin();

        $optedIn = Admin::factory()->create(['image' => null, 'use_gravatar' => true]);
        $optedOut = Admin::factory()->create(['image' => null, 'use_gravatar' => false]);

        // The grid only renders a gravatar the cached upstream lookup already confirmed, so prime
        // both rows: the opted-out row must then stay empty because of the flag, not a cache miss.
        foreach ([$optedIn, $optedOut] as $user) {
            Cache::put('admin.gravatar.'.md5(mb_strtolower(trim($user->email))), [
                'found'        => true,
                'body'         => '',
                'content_type' => 'image/png',
            ]);
        }

        $records = collect(app(UserDataGrid::class)->toJson()->getData(true)['records']);

        $in = $records->firstWhere('user_id', $optedIn->id);
        $out = $records->firstWhere('user_id', $optedOut->id);

        expect($in['user_img'])->toContain('avatar/u/');
        expect($out['user_img'])->toBeNull();
    });
});
