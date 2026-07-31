<?php

use Webkul\User\Models\Admin;

it('throttles the oauth token endpoint after the limit', function () {
    $admin = Admin::factory()->create(['password' => bcrypt('password')]);

    $statuses = [];

    for ($i = 0; $i < 12; $i++) {
        $statuses[] = $this->postJson('/oauth/token', [
            'grant_type'    => 'password',
            'client_id'     => '00000000-0000-0000-0000-000000000000',
            'client_secret' => 'invalid-secret',
            'username'      => $admin->email,
            'password'      => 'wrong-password',
            'scope'         => '',
        ])->getStatusCode();
    }

    expect($statuses)->toContain(429);
});
