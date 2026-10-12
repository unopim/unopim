<?php

use Illuminate\Support\Facades\Hash;
use Webkul\AdminApi\Services\ApiUserProvisioner;
use Webkul\User\Models\Role;

it('provisions a roleless robot admin', function () {
    $result = app(ApiUserProvisioner::class)->provisionForIntegration('WooCommerce Sync');

    $admin = $result['admin'];

    expect($admin->isApiUser())->toBeTrue()
        ->and($admin->status)->toBe(1)
        ->and($admin->role_id)->toBeNull()
        ->and($admin->email)->toEndWith('@api.local')
        ->and($result['password'])->toBeString()
        ->and(strlen($result['password']))->toBe(32)
        ->and(Hash::check($result['password'], $admin->password))->toBeTrue();

    expect($admin->password)->not->toBe($result['password']);
});

it('does not create a system API role', function () {
    app(ApiUserProvisioner::class)->provisionForIntegration('Shopify Sync');

    expect(Role::where('name', 'API')->exists())->toBeFalse();
});
