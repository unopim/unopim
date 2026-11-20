<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\ModelDiscovery;

/*
 * fetch-models and test-connection swap a masked api_key for the saved
 * platform's key. A user who can only view platforms must not be able to
 * point that stored key at a host of their choosing.
 */
const STORED_KEY = 'sk-stored-secret-1234567890';
const STORED_URL = 'https://1.1.1.1/v1';
const FOREIGN_URL = 'https://8.8.8.8/v1';

function storedKeyPlatform(): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'      => 'Stored',
        'provider'   => AiProvider::Custom->value,
        'api_url'    => STORED_URL,
        'api_key'    => STORED_KEY,
        'models'     => 'gpt-4o',
        'is_default' => true,
        'status'     => true,
    ]);
}

function loginAsPlatformViewer(): void
{
    test()->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);
}

function loginAsPlatformEditor(): void
{
    test()->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.edit']);
}

function fakeChatCompletion(): void
{
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]], 200)]);
}

function sentToHost(string $host): bool
{
    return collect(Http::recorded())
        ->contains(fn (array $pair): bool => $pair[0]->toPsrRequest()->getUri()->getHost() === $host);
}

describe('fetch-models', function () {
    it('refuses a viewer sending the masked stored key to a foreign api_url', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformViewer();

        $this->mock(ModelDiscovery::class)->shouldNotReceive('discover');

        $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
            'id'       => $platform->id,
            'provider' => AiProvider::Custom->value,
            'api_key'  => '********',
            'api_url'  => FOREIGN_URL,
        ])->assertStatus(422)->assertJsonValidationErrors('api_url');
    });

    it('sends the stored key only to the saved api_url when a viewer omits it', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformViewer();

        $this->mock(ModelDiscovery::class)
            ->shouldReceive('discover')
            ->once()
            ->withArgs(fn (AiProvider $provider, ?string $apiKey, ?string $apiUrl): bool => $apiKey === STORED_KEY && $apiUrl === STORED_URL)
            ->andReturn(['models' => ['gpt-4o'], 'released' => [], 'api_url' => STORED_URL]);

        $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
            'id'       => $platform->id,
            'provider' => AiProvider::Custom->value,
            'api_key'  => '********',
        ])->assertOk()->assertJsonPath('api_url', STORED_URL);
    });

    it('rejects a masked key for an unknown platform without calling out', function () {
        loginAsPlatformViewer();

        $this->mock(ModelDiscovery::class)->shouldNotReceive('discover');

        $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
            'id'       => 999999,
            'provider' => AiProvider::Custom->value,
            'api_key'  => '********',
            'api_url'  => STORED_URL,
        ])->assertStatus(422)->assertJsonValidationErrors('id');
    });

    it('lets an editor send a new key to any public api_url', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformEditor();

        $this->mock(ModelDiscovery::class)
            ->shouldReceive('discover')
            ->once()
            ->withArgs(fn (AiProvider $provider, ?string $apiKey, ?string $apiUrl): bool => $apiKey === 'sk-new-key-1234567890' && $apiUrl === FOREIGN_URL)
            ->andReturn(['models' => ['gpt-4o'], 'released' => [], 'api_url' => FOREIGN_URL]);

        $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
            'id'       => $platform->id,
            'provider' => AiProvider::Custom->value,
            'api_key'  => 'sk-new-key-1234567890',
            'api_url'  => FOREIGN_URL,
        ])->assertOk();
    });
});

describe('test-connection', function () {
    it('refuses a viewer sending the masked stored key to a foreign api_url', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformViewer();
        fakeChatCompletion();

        $this->postJson(route('admin.magic_ai.platform.test'), [
            'id'       => $platform->id,
            'provider' => AiProvider::Custom->value,
            'api_key'  => '********',
            'api_url'  => FOREIGN_URL,
            'models'   => 'gpt-4o',
        ])->assertStatus(422)->assertJsonValidationErrors('api_url');

        Http::assertNothingSent();
    });

    it('refuses a viewer replaying the stored key on another provider', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformViewer();
        fakeChatCompletion();

        $this->postJson(route('admin.magic_ai.platform.test'), [
            'id'       => $platform->id,
            'provider' => AiProvider::OpenAI->value,
            'api_key'  => '********',
            'models'   => 'gpt-4o',
        ])->assertStatus(422)->assertJsonValidationErrors('api_url');

        Http::assertNothingSent();
    });

    it('sends the stored key only to the saved api_url when a viewer omits it', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformViewer();
        fakeChatCompletion();

        $this->postJson(route('admin.magic_ai.platform.test'), [
            'id'       => $platform->id,
            'provider' => AiProvider::Custom->value,
            'api_key'  => '********',
            'models'   => 'gpt-4o',
        ])->assertOk()->assertJsonPath('success', true);

        Http::assertSent(fn (Request $request): bool => $request->toPsrRequest()->getUri()->getHost() === '1.1.1.1'
            && $request->hasHeader('Authorization', 'Bearer '.STORED_KEY));
        expect(sentToHost('8.8.8.8'))->toBeFalse();
    });

    it('rejects a masked key for an unknown platform without calling out', function () {
        loginAsPlatformViewer();
        fakeChatCompletion();

        $this->postJson(route('admin.magic_ai.platform.test'), [
            'id'       => 999999,
            'provider' => AiProvider::Custom->value,
            'api_key'  => '********',
            'api_url'  => STORED_URL,
            'models'   => 'gpt-4o',
        ])->assertStatus(422)->assertJsonValidationErrors('id');

        Http::assertNothingSent();
    });

    it('lets an editor send a new key to any public api_url', function () {
        $platform = storedKeyPlatform();
        loginAsPlatformEditor();
        fakeChatCompletion();

        $this->postJson(route('admin.magic_ai.platform.test'), [
            'id'       => $platform->id,
            'provider' => AiProvider::Custom->value,
            'api_key'  => 'sk-new-key-1234567890',
            'api_url'  => FOREIGN_URL,
            'models'   => 'gpt-4o',
        ])->assertOk()->assertJsonPath('success', true);

        expect(sentToHost('8.8.8.8'))->toBeTrue();
    });
});
