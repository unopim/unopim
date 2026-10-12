<?php

use Webkul\User\Tests\Concerns\UserAssertions;

uses(UserAssertions::class);

it('renders the integration create page without an admin_id dropdown', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.configuration.integrations.create'));

    $response->assertOk();
});
