<?php

use Illuminate\Support\Facades\Date;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function (): void {
    config(['elasticsearch.enabled' => false]);
});

dataset('unusable timezones', ['blank' => '', 'invalid' => 'Mars/Phobos']);

it('opens the product grid with the updated at column for a user with an unusable timezone', function (string $timezone): void {
    $this->loginAsAdmin(Admin::factory()->create(['timezone' => $timezone]));

    Product::factory()->create();

    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), ['managedColumns' => ['sku', 'updated_at']])
        ->assertOk();
})->with('unusable timezones');

it('opens the product edit page for a user with an unusable timezone', function (string $timezone): void {
    $this->loginAsAdmin(Admin::factory()->create(['timezone' => $timezone]));

    $product = Product::factory()->create();

    $this->get(route('admin.catalog.products.edit', $product->id))->assertOk();
})->with('unusable timezones');

it('opens the product history for a user with an unusable timezone', function (string $timezone): void {
    $this->loginAsAdmin(Admin::factory()->create(['timezone' => $timezone]));

    $product = Product::factory()->create();

    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.history.index', ['entity' => 'product', 'id' => $product->id]))
        ->assertOk();
})->with('unusable timezones');

it('formats dates in the app timezone for an unusable user timezone and in the user timezone otherwise', function (): void {
    $date = Date::parse('2026-01-01 12:00:00', 'UTC');

    config(['app.timezone' => 'UTC']);

    $this->loginAsAdmin(Admin::factory()->create(['timezone' => '']));

    expect(core()->formatDateWithTimeZone($date->copy(), 'Y-m-d H:i'))->toBe('2026-01-01 12:00');

    $this->loginAsAdmin(Admin::factory()->create(['timezone' => 'Asia/Kolkata']));

    expect(core()->formatDateWithTimeZone($date->copy(), 'Y-m-d H:i'))->toBe('2026-01-01 17:30');
});

it('rejects an invalid timezone when creating a user', function (): void {
    $this->loginAsAdmin();

    $this->postJson(route('admin.settings.users.store'), [
        'name'                  => 'Invalid Timezone User',
        'email'                 => 'invalid.tz@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => Role::firstOrFail()->id,
        'ui_locale_id'          => Locale::where('code', 'en_US')->value('id'),
        'timezone'              => 'Mars/Phobos',
        'status'                => 1,
    ])->assertUnprocessable()->assertJsonValidationErrors('timezone');

    $this->assertDatabaseMissing('admins', ['email' => 'invalid.tz@example.com']);
});

it('rejects an invalid timezone when editing a user and keeps the stored one', function (): void {
    $this->loginAsAdmin();

    $user = Admin::factory()->create(['timezone' => 'Asia/Kolkata']);

    $this->putJson(route('admin.settings.users.update'), [
        'id'           => $user->id,
        'name'         => 'Invalid Timezone User',
        'email'        => $user->email,
        'role_id'      => $user->role_id,
        'ui_locale_id' => Locale::where('code', 'en_US')->value('id'),
        'timezone'     => 'Mars/Phobos',
        'status'       => 1,
    ])->assertUnprocessable()->assertOnlyJsonValidationErrors('timezone');

    expect($user->fresh()->timezone)->toBe('Asia/Kolkata');
});
