<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
use Webkul\User\Models\Admin;

it('should revoke all Passport tokens when an admin changes their password (Issue #735)', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('old-password')]);

    $clientRepo = new ClientRepository;
    $client = $clientRepo->createPasswordGrantClient($admin->id, 'Test client', env('APP_URL'), 'admins');

    $token = new Token;
    $token->id = 'tok-'.uniqid();
    $token->user_id = $admin->id;
    $token->client_id = $client->getKey();
    $token->name = 'Test Token';
    $token->scopes = [];
    $token->revoked = false;
    $token->expires_at = now()->addHour();
    $token->save();

    $this->actingAs($admin, 'admin');

    $response = $this->put(route('admin.account.update'), [
        'id'                    => $admin->id,
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'current_password'      => 'old-password',
        'password'              => 'new-password-123',
        'password_confirmation' => 'new-password-123',
        'ui_locale_id'          => $admin->ui_locale_id,
        'timezone'              => 'UTC',
    ]);

    $token->refresh();
    expect((bool) $token->revoked)->toBeTrue();
});
