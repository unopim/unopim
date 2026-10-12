<?php

use Illuminate\Routing\Middleware\ThrottleRequests;

it('should redirect to forget-password when visiting reset-password without a token', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $response = $this->get('/admin/reset-password');

    $response->assertRedirect(route('admin.forget_password.create'));
});
