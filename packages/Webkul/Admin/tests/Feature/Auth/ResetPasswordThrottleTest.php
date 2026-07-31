<?php

it('throttles repeated admin reset-password attempts to prevent token brute force', function () {
    $response = null;

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $response = $this->post(route('admin.reset_password.store'), [
            'token'                 => 'brute-force-token-'.$attempt,
            'email'                 => 'victim@example.test',
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);
    }

    $response->assertStatus(429);
});
