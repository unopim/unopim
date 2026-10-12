<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

use function Pest\Laravel\actingAs;

it('offers the tracked save bar on the account edit page', function () {
    $admin = Admin::factory()->create([
        'password' => Hash::make('admin123'),
        'status'   => 1,
    ]);

    $response = actingAs($admin, 'admin')->get(route('admin.account.edit'));

    $response->assertOk();
    $response->assertSee('v-unsaved-changes', false);
    $response->assertSee('data-unsaved-save', false);
    $response->assertDontSee('form="account-edit-form"', false);
});
