<?php

it('throttles repeated admin login attempts to prevent brute force', function () {
    $response = null;

    for ($attempt = 0; $attempt < 7; $attempt++) {
        $response = $this->post(route('admin.session.store'), [
            'email'    => 'attacker@example.test',
            'password' => 'wrong-password-'.$attempt,
        ]);
    }

    $response->assertStatus(429);
});
