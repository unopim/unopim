<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Token;
use Webkul\AdminApi\Models\Apikey;
use Webkul\AdminApi\Models\Client;
use Webkul\User\Models\Admin;
use Webkul\User\Tests\Concerns\UserAssertions;

uses(UserAssertions::class);

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();
});

/** Create a password grant OAuth client owned by the given admin id. */
function regenerateSecretClient(?int $ownerId = null): Client
{
    return Client::create([
        'user_id'                => $ownerId,
        'name'                   => 'Regenerate secret client',
        'secret'                 => Str::random(40),
        'provider'               => 'admins',
        'redirect'               => '',
        'personal_access_client' => false,
        'password_client'        => true,
        'revoked'                => false,
    ]);
}

/** Create an integration owned by an admin of the given type and return its OAuth client. */
function regenerateSecretIntegrationClient(string $ownerType): Client
{
    $owner = Admin::factory()->create(['type' => $ownerType]);

    $client = regenerateSecretClient($owner->id);

    Apikey::factory()->create([
        'admin_id'        => $owner->id,
        'oauth_client_id' => $client->getKey(),
        'permission_type' => 'all',
    ]);

    return $client;
}

/** Issue a live access token for the given OAuth client. */
function regenerateSecretToken(Client $client): Token
{
    return Token::create([
        'id'        => Str::random(80),
        'user_id'   => null,
        'client_id' => $client->getKey(),
        'name'      => 'Regenerate secret token',
        'scopes'    => [],
        'revoked'   => false,
    ]);
}

/** Read the stored secret hash of the given OAuth client. */
function regenerateSecretStoredHash(Client $client): string
{
    return DB::table('oauth_clients')->where('id', $client->getKey())->value('secret');
}

it('regenerates the secret of an integration owned by an api user and revokes its tokens', function (): void {
    $client = regenerateSecretIntegrationClient('api');

    $token = regenerateSecretToken($client);

    $response = $this->post(route('admin.configuration.integrations.re_generate_secret_key'), [
        'oauth_client_id' => $client->getKey(),
    ]);

    $response->assertOk();

    expect(Hash::check($response->json('secret_key'), regenerateSecretStoredHash($client)))->toBeTrue()
        ->and($token->fresh()->revoked)->toBeTrue();
});

it('refuses to regenerate the secret of a client that is not an api user integration', function (Client $client): void {
    $token = regenerateSecretToken($client);

    $storedHash = regenerateSecretStoredHash($client);

    $this->post(route('admin.configuration.integrations.re_generate_secret_key'), [
        'oauth_client_id' => $client->getKey(),
    ])
        ->assertNotFound()
        ->assertJson(['message' => trans('admin::app.configuration.integrations.client-not-found')]);

    expect(regenerateSecretStoredHash($client))->toBe($storedHash)
        ->and($token->fresh()->revoked)->toBeFalse();
})->with([
    'client without an integration'        => fn (): Client => regenerateSecretClient(),
    'integration owned by a human admin'   => fn (): Client => regenerateSecretIntegrationClient('user'),
]);
