<?php

use Webkul\User\Tests\Concerns\UserAssertions;

// admin.configuration.integrations.* are session-guarded web routes. The
// AdminApi test directory is bound to ApiTestCase (OAuth token auth) in
// tests/Pest.php, so mix in UserAssertions here for loginAsAdmin().
uses(UserAssertions::class);

it('renders the integration create page without an admin_id dropdown', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.configuration.integrations.create'));

    $response->assertOk();
});
