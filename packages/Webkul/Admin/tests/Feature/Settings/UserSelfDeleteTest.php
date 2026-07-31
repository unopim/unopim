<?php

use Webkul\User\Models\Admin;

use function Pest\Laravel\put;

it('returns a json error instead of a null response when the last admin tries to self-delete', function () {
    $admin = $this->loginAsAdmin();

    Admin::query()->whereKeyNot($admin->id)->delete();

    expect(Admin::query()->count())->toBe(1);

    put(route('admin.settings.users.destroy'), ['password' => 'password'])
        ->assertStatus(400)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.users.delete-last'),
        ]);

    expect(Admin::query()->count())->toBe(1);
});

it('returns a json error when the self-delete password is incorrect', function () {
    $this->loginAsAdmin();

    Admin::factory()->create();

    put(route('admin.settings.users.destroy'), ['password' => 'wrong-password'])
        ->assertStatus(404)
        ->assertJsonFragment([
            'message' => trans('admin::app.settings.users.incorrect-password'),
        ]);
});
