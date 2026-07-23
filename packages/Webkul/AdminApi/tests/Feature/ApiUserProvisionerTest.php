<?php

use Illuminate\Support\Facades\Hash;
use Webkul\AdminApi\Services\ApiUserProvisioner;
use Webkul\AdminApi\Support\ApiRole;

it('provisions a robot admin bound to the API role', function () {
    $result = app(ApiUserProvisioner::class)->provisionForIntegration('WooCommerce Sync');

    $admin = $result['admin'];

    expect($admin->isApiUser())->toBeTrue()
        ->and($admin->status)->toBe(1)
        ->and($admin->role_id)->toBe(ApiRole::ensure()->id)
        ->and($admin->email)->toEndWith('@api.local')
        ->and($result['password'])->toBeString()
        ->and(strlen($result['password']))->toBe(32)
        ->and(Hash::check($result['password'], $admin->password))->toBeTrue();

    // Password is never returned in the persisted plaintext form.
    expect($admin->password)->not->toBe($result['password']);
});
