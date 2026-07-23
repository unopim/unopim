<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

use function Pest\Laravel\actingAs;

it('renders a submit save button on the account edit page', function () {
    $admin = Admin::factory()->create([
        'password' => Hash::make('admin123'),
        'status'   => 1,
    ]);

    $response = actingAs($admin, 'admin')->get(route('admin.account.edit'));

    $response->assertOk();

    $saveLabel = trans('admin::app.account.edit.save-btn');

    $response->assertSee($saveLabel);
    $response->assertSee('type="submit"', false);
    $response->assertSee('form="account-edit-form"', false);
});
