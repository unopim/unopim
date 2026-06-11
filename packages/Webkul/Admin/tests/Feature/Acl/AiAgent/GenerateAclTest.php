<?php

use Webkul\Admin\Models\User;

it('should restrict generate process route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']); // User without 'ai-agent.generate'

    $response = $this->postJson(route('ai-agent.generate.process'), [
        'images'        => ['image.jpg'],
        'credential_id' => 1,
        'instruction'   => 'Test',
    ]);

    $response->assertStatus(403);
});

it('should allow generate process route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.generate']);

    $response = $this->postJson(route('ai-agent.generate.process'), [
        'images'        => ['image.jpg'],
        'credential_id' => 1,
        'instruction'   => 'Test',
    ]);

    // We expect a validation error or something other than 403, meaning ACL passed
    expect($response->status())->not->toBe(403);
});
