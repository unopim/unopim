<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;
use Webkul\User\Tests\Concerns\UserAssertions;

// admin.configuration.integrations.* are session-guarded web routes. The
// AdminApi test directory is bound to ApiTestCase (OAuth token auth) in
// tests/Pest.php, so mix in UserAssertions here for loginAsAdmin().
uses(UserAssertions::class);

it('creates an integration bound to a fresh robot without an admin_id input', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Shopify',
        'permission_type' => 'all',
    ]);

    $key = Apikey::where('name', 'Shopify')->firstOrFail();
    $robot = Admin::findOrFail($key->admin_id);

    expect($robot->isApiUser())->toBeTrue()
        ->and($robot->email)->toEndWith('@api.local');

    $response->assertRedirect(route('admin.configuration.integrations.edit', $key->id));
});
