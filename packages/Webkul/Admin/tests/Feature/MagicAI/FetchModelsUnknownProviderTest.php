<?php

beforeEach(function (): void {
    $this->loginAsAdmin();
});

it('rejects an unsupported provider with a 422 validation error', function (): void {
    $this->postJson(route('admin.magic_ai.platform.fetch_models'), ['provider' => 'not-a-provider'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('provider');
});

it('rejects an unsupported provider on test-connection with a 422', function (): void {
    $this->postJson(route('admin.magic_ai.platform.test'), ['provider' => 'not-a-provider', 'models' => 'gpt-4o'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('provider');
});

it('rejects a non-string api_url with a 422 instead of a server error', function (string $routeName): void {
    $this->postJson(route($routeName), ['provider' => 'openai', 'models' => 'gpt-4o', 'api_url' => ['x']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('api_url');
})->with(['admin.magic_ai.platform.fetch_models', 'admin.magic_ai.platform.test']);
