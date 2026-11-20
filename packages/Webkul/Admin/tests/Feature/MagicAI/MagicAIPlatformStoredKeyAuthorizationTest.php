<?php

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\MagicAI\MagicAIPlatformController;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

beforeEach(function (): void {
    $this->storedKey = 'sk-test-StoredKeyForAuthorizationChecks0123';

    $this->platform = MagicAIPlatform::create([
        'label'      => 'Stored Key Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => $this->storedKey,
        'models'     => 'gpt-4o-mini',
        'status'     => true,
        'is_default' => false,
    ]);

    $this->storedKeyPayload = [
        'id'       => $this->platform->id,
        'provider' => 'openai',
        'api_key'  => '********',
        'api_url'  => 'https://attacker.example.com/v1',
        'models'   => 'gpt-4o-mini',
    ];
});

it('forbids a view-only user from fetching models with the stored key', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);

    $this->postJson(route('admin.magic_ai.platform.fetch_models'), $this->storedKeyPayload)
        ->assertForbidden();
});

it('forbids a view-only user from testing a connection with the stored key', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);

    $this->postJson(route('admin.magic_ai.platform.test'), $this->storedKeyPayload)
        ->assertForbidden();
});

it('forbids a create-only user from using the stored key of a saved platform', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.create']);

    $this->postJson(route('admin.magic_ai.platform.fetch_models'), $this->storedKeyPayload)
        ->assertForbidden();
});

it('forbids a view-only user from probing a provider with a typed key', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);

    $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
        'provider' => 'openai',
        'api_key'  => 'sk-test-TypedKeyOfTheViewOnlyUser012345',
        'api_url'  => 'https://api.openai.com/v1',
    ])->assertForbidden();
});

it('lets a create-only user reach provider checks with a key they typed', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.create']);

    $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
        'provider' => 'openai',
        'api_key'  => 'sk-test-TypedKeyOfTheCreator0123456789',
        'api_url'  => 'http://127.0.0.1/v1',
    ])->assertUnprocessable();
});

it('sends the stored key only to the stored api url, never to the submitted one', function (): void {
    $this->app->instance('request', Request::create('/', 'POST', $this->storedKeyPayload + [
        'extras' => json_encode(['url' => 'https://attacker.example.com/v1', 'key' => 'other']),
    ]));

    $controller = new class(app(MagicAIPlatformRepository::class)) extends MagicAIPlatformController
    {
        /**
         * Expose the provider overrides the connection test would use.
         *
         * @return array<string, mixed>
         */
        public function overrides(): array
        {
            return $this->providerOverridesFromRequest();
        }
    };

    expect($controller->overrides())
        ->toMatchArray(['key' => $this->storedKey, 'url' => 'https://api.openai.com/v1']);
});

it('calls the provider the stored key was saved for, whatever provider is submitted', function (): void {
    $this->app->instance('request', Request::create('/', 'POST', ['provider' => 'gemini'] + $this->storedKeyPayload));

    $controller = new class(app(MagicAIPlatformRepository::class)) extends MagicAIPlatformController
    {
        /**
         * Expose the provider the connection test and model listing would call.
         */
        public function provider(): ?string
        {
            return $this->resolveProvider();
        }
    };

    expect($controller->provider())->toBe('openai');
});

it('tests a reused stored key with the saved extras, never the request ones', function (): void {
    $this->platform->update(['extras' => ['deployment' => 'saved-deployment']]);

    $this->app->instance('request', Request::create('/', 'POST', $this->storedKeyPayload + [
        'extras' => json_encode(['deployment' => 'request-deployment', 'driver' => 'bedrock', 'region' => 'attacker.example.com']),
    ]));

    $controller = new class(app(MagicAIPlatformRepository::class)) extends MagicAIPlatformController
    {
        /**
         * Expose the provider overrides the connection test would use.
         *
         * @return array<string, mixed>
         */
        public function overrides(): array
        {
            return $this->providerOverridesFromRequest();
        }
    };

    expect($controller->overrides())->toBe([
        'key'        => $this->storedKey,
        'url'        => 'https://api.openai.com/v1',
        'deployment' => 'saved-deployment',
    ]);
});

it('drops request extras that would swap the driver or region for a typed key', function (): void {
    $this->app->instance('request', Request::create('/', 'POST', [
        'provider' => 'openai',
        'api_key'  => 'sk-typed-key',
        'api_url'  => 'https://1.1.1.1/v1',
        'extras'   => json_encode(['driver' => 'bedrock', 'region' => 'attacker.example.com', 'headers' => ['Host' => 'x'], 'organization' => 'org-1']),
    ]));

    $controller = new class(app(MagicAIPlatformRepository::class)) extends MagicAIPlatformController
    {
        /**
         * Expose the provider overrides the connection test would use.
         *
         * @return array<string, mixed>
         */
        public function overrides(): array
        {
            return $this->providerOverridesFromRequest();
        }
    };

    expect($controller->overrides())->toBe([
        'key'          => 'sk-typed-key',
        'url'          => 'https://1.1.1.1/v1',
        'organization' => 'org-1',
    ]);
});

it('keeps only allowlisted extras on a saved platform', function (): void {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Extras Allowlist',
        'provider' => 'openai',
        'api_key'  => 'sk-test-ExtrasAllowlist0123456789',
        'models'   => 'gpt-4o-mini',
        'extras'   => json_encode(['deployment' => 'gpt-4o', 'driver' => 'bedrock', 'region' => 'attacker.example.com', 'url' => 'http://169.254.169.254/']),
    ])->assertOk();

    expect(MagicAIPlatform::where('label', 'Extras Allowlist')->firstOrFail()->extras)->toBe(['deployment' => 'gpt-4o']);
});
