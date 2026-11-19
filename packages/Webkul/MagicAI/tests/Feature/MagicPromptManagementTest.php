<?php

use Webkul\MagicAI\Models\MagicPrompt;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('stores a reusable prompt', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.prompt.store'), [
        'prompt'  => 'Describe @sku in two sentences.',
        'title'   => 'Short description',
        'type'    => 'product',
        'purpose' => 'text_generation',
    ])->assertOk();

    $this->assertDatabaseHas('magic_ai_prompts', ['title' => 'Short description']);
});

it('rejects a prompt with an unsupported purpose', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.prompt.store'), [
        'prompt'  => 'Describe the product.',
        'title'   => 'Bad purpose',
        'type'    => 'product',
        'purpose' => 'mining',
    ])->assertJsonValidationErrors('purpose');
});

it('requires the prompt body and title', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.prompt.store'), ['purpose' => 'text_generation'])
        ->assertJsonValidationErrors(['prompt', 'title', 'type']);
});

it('updates a prompt and points the caller back at the listing', function () {
    $this->loginWithPermissions('all');

    $prompt = MagicPrompt::factory()->create();

    putJson(route('admin.magic_ai.prompt.update'), [
        'id'      => $prompt->id,
        'prompt'  => 'Rewritten prompt body.',
        'title'   => 'Rewritten',
        'type'    => 'product',
        'purpose' => 'text_generation',
    ])->assertOk()->assertJson(['redirect_url' => route('admin.magic_ai.prompt.index')]);

    expect($prompt->fresh()->title)->toBe('Rewritten');
});

it('deletes a prompt', function () {
    $this->loginWithPermissions('all');

    $prompt = MagicPrompt::factory()->create();

    deleteJson(route('admin.magic_ai.prompt.delete', $prompt->id))->assertOk();

    expect(MagicPrompt::find($prompt->id))->toBeNull();
});

it('lists the default prompts for the requested purpose and entity type', function () {
    $this->loginWithPermissions('all');

    MagicPrompt::factory()->create(['title' => 'Product text', 'type' => 'product', 'purpose' => 'text_generation']);
    MagicPrompt::factory()->create(['title' => 'Category text', 'type' => 'category', 'purpose' => 'text_generation']);

    $response = $this->getJson(route('admin.magic_ai.default_prompt', [
        'entity_type' => 'product',
        'purpose'     => 'text_generation',
    ]))->assertOk();

    expect(array_column($response->json('prompts'), 'title'))
        ->toContain('Product text')
        ->not->toContain('Category text');
});

it('denies the default prompt listing without the ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->getJson(route('admin.magic_ai.default_prompt'))->assertForbidden();
});

it('denies system prompt administration without its own permission', function (string $method, string $route) {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->json($method, route($route, ['id' => 1]))->assertForbidden();
})->with([
    ['get', 'admin.magic_ai.system_prompt.index'],
    ['post', 'admin.magic_ai.system_prompt.store'],
    ['put', 'admin.magic_ai.system_prompt.update'],
    ['delete', 'admin.magic_ai.system_prompt.delete'],
]);
