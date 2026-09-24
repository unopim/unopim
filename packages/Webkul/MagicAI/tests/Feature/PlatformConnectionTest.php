<?php

use Laravel\Ai\AnonymousAgent;
use Webkul\MagicAI\Enums\AiProvider;

use function Pest\Laravel\postJson;

function testConnectionPayload(array $overrides = []): array
{
    return array_merge([
        'provider' => AiProvider::OpenAI->value,
        'api_key'  => 'sk-secret-key',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-4o-mini',
    ], $overrides);
}

it('reports a successful connection when the provider answers', function () {
    $this->loginWithPermissions('all');

    AnonymousAgent::fake(['OK']);

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload())
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('tests the connection against every remotely hosted provider', function (AiProvider $provider) {
    $this->loginWithPermissions('all');

    AnonymousAgent::fake(['OK']);

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload([
        'provider' => $provider->value,
        'api_url'  => $provider->defaultUrl() ?: 'https://api.openai.com/v1',
    ]))->assertOk()->assertJson(['success' => true]);
})->with(array_filter(AiProvider::cases(), fn (AiProvider $provider): bool => $provider !== AiProvider::Ollama));

// The SSRF guard resolves the base url and rejects loopback addresses, so a
// self-hosted Ollama on its default endpoint cannot be connection-tested.
it('blocks a connection test against a loopback endpoint such as a local Ollama', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload([
        'provider' => AiProvider::Ollama->value,
        'api_url'  => AiProvider::Ollama->defaultUrl(),
        'models'   => 'llama3.2',
    ]))->assertUnprocessable()->assertJson(['success' => false]);
});

it('pings a text model rather than an image-only one', function () {
    $this->loginWithPermissions('all');

    AnonymousAgent::fake(['OK']);

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload([
        'models' => 'dall-e-3,gpt-4o-mini',
    ]))->assertOk();

    AnonymousAgent::assertPrompted(fn ($prompt): bool => $prompt->model === 'gpt-4o-mini');
});

it('refuses to test a connection against an internal host', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload(['api_url' => 'http://169.254.169.254']))
        ->assertUnprocessable()
        ->assertJson(['success' => false]);
});

it('refuses to test a custom platform without a base url', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload([
        'provider' => AiProvider::Custom->value,
        'api_url'  => '',
    ]))->assertUnprocessable();
});

it('reports the failure when the provider rejects the credential', function () {
    $this->loginWithPermissions('all');

    AnonymousAgent::fake(fn () => throw new RuntimeException('Incorrect API key provided'));

    $response = postJson(route('admin.magic_ai.platform.test'), testConnectionPayload())->assertBadRequest();

    expect($response->json('success'))->toBeFalse()
        ->and($response->json('message'))->toBeString()->not->toBe('');
});

it('requires a model list to test a connection', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload(['models' => '']))
        ->assertJsonValidationErrors('models');
});

it('rejects unsafe extras on a connection test', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload(['extras' => '{"key":"stolen"}']))
        ->assertJsonValidationErrors('extras');
});

it('refuses to fetch models from an internal host', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.fetch_models'), [
        'provider' => AiProvider::OpenAI->value,
        'api_key'  => 'sk-secret-key',
        'api_url'  => 'http://127.0.0.1:9200',
    ])->assertUnprocessable();
});

it('requires a provider to fetch models', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.fetch_models'), ['api_key' => 'sk-secret-key'])
        ->assertJsonValidationErrors('provider');
});

it('falls back to the next text model when the provider refuses the first one', function () {
    $this->loginWithPermissions('all');

    $calls = 0;

    AnonymousAgent::fake(function () use (&$calls): string {
        if (++$calls === 1) {
            throw new RuntimeException('This model is no longer available to new users');
        }

        return 'OK';
    });

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload(['models' => 'gemini-2.5-flash,gemini-3.1-flash-lite']))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($calls)->toBe(2);
});

it('stops after three refused models and reports the first failure', function () {
    $this->loginWithPermissions('all');

    $calls = 0;

    AnonymousAgent::fake(function () use (&$calls): string {
        $calls++;

        throw new RuntimeException('Incorrect API key provided');
    });

    postJson(route('admin.magic_ai.platform.test'), testConnectionPayload(['models' => 'a-1,b-1,c-1,d-1']))
        ->assertBadRequest()
        ->assertJson(['success' => false]);

    expect($calls)->toBe(3);
});
