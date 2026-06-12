<?php

it('should restrict generate process route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $response = $this->postJson(route('ai-agent.generate.process'), [
        'images'        => ['image.jpg'],
        'credential_id' => 1,
        'instruction'   => 'Test',
    ]);

    $response->assertStatus(401);
});

it('should allow generate process route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.generate']);

    $response = $this->postJson(route('ai-agent.generate.process'), [
        'images'        => ['image.jpg'],
        'credential_id' => 1,
        'instruction'   => 'Test',
    ]);

    // ACL passes (not 403); the request is then rejected by form validation (422),
    // so the AI image-to-product service is never invoked (no side effects).
    $response->assertStatus(422);
});
