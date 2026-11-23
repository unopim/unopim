<?php

beforeEach(function () {
    config(['app.url' => 'https://pim.example.com']);
});

it('remembers a referring admin page on the same origin', function () {
    $this->withHeader('referer', 'https://pim.example.com/admin/catalog/products')
        ->get(route('admin.session.create'))
        ->assertOk()
        ->assertSessionHas('url.intended', 'https://pim.example.com/admin/catalog/products');
});

it('ignores a referer that is not a safe admin page', function (string $referer) {
    $this->withHeader('referer', $referer)
        ->get(route('admin.session.create'))
        ->assertOk()
        ->assertSessionMissing('url.intended');
})->with([
    'another port'          => 'https://pim.example.com:6666/admin/catalog/products',
    'another scheme'        => 'http://pim.example.com/admin/catalog/products',
    'another host'          => 'https://evil.example.com/admin/catalog/products',
    'a non-get route'       => 'https://pim.example.com/admin/logout',
    'an unknown path'       => 'https://pim.example.com/admin/does-not-exist',
    'a guest auth page'     => 'https://pim.example.com/admin/forget-password',
    'a page outside /admin' => 'https://pim.example.com/api/v1/rest/locales',
]);

it('ignores an unsafe referer on the forgot password page too', function () {
    $this->withHeader('referer', 'https://pim.example.com:6666/admin/catalog/products')
        ->get(route('admin.forget_password.create'))
        ->assertOk()
        ->assertSessionMissing('url.intended');
});

it('sends the admin to the landing page when the remembered page is unsafe', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['dashboard']);

    $this->withSession(['url.intended' => 'https://pim.example.com:6666/admin/phish'])
        ->post(route('admin.session.store'), [
            'email'    => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('admin.dashboard.index'));
});
