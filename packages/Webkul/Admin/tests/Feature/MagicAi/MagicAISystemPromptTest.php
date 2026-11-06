<?php

use Webkul\MagicAI\Models\MagicAISystemPrompt;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return MagicAI System Prompts DataGrid', function () {
    $this->get(route('admin.magic_ai.system_prompt.index'))
        ->assertOk();
});

it('should return the System Prompt DataGrid as JSON for AJAX requests', function () {
    MagicAISystemPrompt::factory()->create();
    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.magic_ai.system_prompt.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);
    $this->assertDatabaseHas($this->getFullTableName(MagicAISystemPrompt::class), [
        'id' => $data['records'][0]['id'],
    ]);
});

it('should return validation error for title and tone', function () {
    $systemPrompt = MagicAISystemPrompt::factory()->make()->toArray();
    unset($systemPrompt['title']);
    unset($systemPrompt['tone']);

    $this->post(route('admin.magic_ai.system_prompt.store'), $systemPrompt)
        ->assertRedirect()
        ->assertSessionHasErrors([
            'title',
            'tone',
        ]);
});

it('should create a new system prompt successfully', function () {
    $systemPrompt = MagicAISystemPrompt::factory()->make()->toArray();
    $this->post(route('admin.magic_ai.system_prompt.store', $systemPrompt))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.configuration.system-prompt.message.save-success')]);
    $this->assertDatabaseHas($this->getFullTableName(MagicAISystemPrompt::class), [
        'title'        => $systemPrompt['title'],
        'tone'         => $systemPrompt['tone'],
        'max_tokens'   => $systemPrompt['max_tokens'],
        'temperature'  => $systemPrompt['temperature'],
        'is_enabled'   => $systemPrompt['is_enabled'],
    ]);
});

it('should update a system prompt successfully', function () {
    $prompt = MagicAISystemPrompt::factory()->create();
    $data = [
        'id'             => $prompt->id,
        'title'          => 'professional',
        'tone'           => 'Be professional and give response in points',
        'max_tokens'     => 1024,
        'temperature'    => 0.4,
        'is_enabled'     => 0,
    ];

    $this->put(route('admin.magic_ai.system_prompt.update'), $data)
        ->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.configuration.system-prompt.message.update-success')]);
    $this->assertDatabaseHas($this->getFullTableName(MagicAISystemPrompt::class), $data);
});

it('should delete system prompt successfully', function () {
    $prompt = MagicAISystemPrompt::factory()->create();
    $this->delete(route('admin.magic_ai.system_prompt.delete', $prompt->id))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.configuration.system-prompt.message.delete-success')]);
    $this->assertDatabaseMissing(
        $this->getFullTableName(MagicAISystemPrompt::class),
        [
            'id' => $prompt->id,
        ]
    );
});

it('rejects an out of range max_tokens when creating a system prompt', function (int $maxTokens) {
    $systemPrompt = MagicAISystemPrompt::factory()->make(['max_tokens' => $maxTokens])->toArray();

    $this->postJson(route('admin.magic_ai.system_prompt.store'), $systemPrompt)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('max_tokens');

    $this->assertDatabaseMissing($this->getFullTableName(MagicAISystemPrompt::class), [
        'title' => $systemPrompt['title'],
    ]);
})->with([
    'below the lower bound' => [MagicAISystemPrompt::MAX_TOKENS_LOWER_BOUND - 1],
    'above the upper bound' => [MagicAISystemPrompt::MAX_TOKENS_UPPER_BOUND + 1],
    'issue example'         => [6000],
]);

it('rejects an out of range max_tokens when updating a system prompt', function (int $maxTokens) {
    $prompt = MagicAISystemPrompt::factory()->create();

    $this->putJson(route('admin.magic_ai.system_prompt.update'), [
        'id'          => $prompt->id,
        'title'       => $prompt->title,
        'tone'        => $prompt->tone,
        'max_tokens'  => $maxTokens,
        'temperature' => $prompt->temperature,
        'is_enabled'  => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('max_tokens');

    expect($prompt->fresh()->max_tokens)->toBe($prompt->max_tokens);
})->with([
    'below the lower bound' => [MagicAISystemPrompt::MAX_TOKENS_LOWER_BOUND - 1],
    'above the upper bound' => [MagicAISystemPrompt::MAX_TOKENS_UPPER_BOUND + 1],
    'issue example'         => [6000],
]);

it('accepts max_tokens at both bounds when creating and updating a system prompt', function (int $maxTokens) {
    $systemPrompt = MagicAISystemPrompt::factory()->make(['max_tokens' => $maxTokens, 'is_enabled' => 0])->toArray();

    $this->postJson(route('admin.magic_ai.system_prompt.store'), $systemPrompt)
        ->assertOk();

    $prompt = MagicAISystemPrompt::factory()->create(['max_tokens' => 1000]);

    $this->putJson(route('admin.magic_ai.system_prompt.update'), [
        'id'          => $prompt->id,
        'title'       => $prompt->title,
        'tone'        => $prompt->tone,
        'max_tokens'  => $maxTokens,
        'temperature' => $prompt->temperature,
        'is_enabled'  => 0,
    ])
        ->assertOk();

    $this->assertDatabaseHas($this->getFullTableName(MagicAISystemPrompt::class), [
        'title'      => $systemPrompt['title'],
        'max_tokens' => $maxTokens,
    ]);

    expect($prompt->fresh()->max_tokens)->toBe($maxTokens);
})->with([
    'lower bound' => [MagicAISystemPrompt::MAX_TOKENS_LOWER_BOUND],
    'upper bound' => [MagicAISystemPrompt::MAX_TOKENS_UPPER_BOUND],
]);

it('renders the same max_tokens range on the create and edit forms', function () {
    $prompt = MagicAISystemPrompt::factory()->create();

    $rule = 'required|integer|'.MagicAISystemPrompt::maxTokensRangeRule();

    $this->get(route('admin.magic_ai.system_prompt.index'))
        ->assertOk()
        ->assertSee($rule, false)
        ->assertSee('max="'.MagicAISystemPrompt::MAX_TOKENS_UPPER_BOUND.'"', false);

    $this->get(route('admin.magic_ai.system_prompt.edit', $prompt->id))
        ->assertOk()
        ->assertSee($rule, false)
        ->assertSee('max="'.MagicAISystemPrompt::MAX_TOKENS_UPPER_BOUND.'"', false)
        ->assertDontSee('max="32768"', false);
});

it('shows the max_tokens range tooltip on the edit form', function () {
    $prompt = MagicAISystemPrompt::factory()->create();

    $this->get(route('admin.magic_ai.system_prompt.edit', $prompt->id))
        ->assertOk()
        ->assertSee(trans('admin::app.configuration.system-prompt.create.max-tokens-tooltip'));
});

it('forbids creating or updating a system prompt with only the view permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

    $prompt = MagicAISystemPrompt::factory()->create(['max_tokens' => 1000]);

    $payload = [
        'title'       => 'professional',
        'tone'        => 'Be professional',
        'max_tokens'  => 2000,
        'temperature' => 0.4,
        'is_enabled'  => 0,
    ];

    $this->postJson(route('admin.magic_ai.system_prompt.store'), $payload)
        ->assertForbidden();

    $this->putJson(route('admin.magic_ai.system_prompt.update'), ['id' => $prompt->id] + $payload)
        ->assertForbidden();

    expect($prompt->fresh()->max_tokens)->toBe(1000);
});

it('allows creating and updating a system prompt with the edit permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit']);

    $prompt = MagicAISystemPrompt::factory()->create(['max_tokens' => 1000]);

    $payload = [
        'title'       => 'professional',
        'tone'        => 'Be professional',
        'max_tokens'  => 2000,
        'temperature' => 0.4,
        'is_enabled'  => 0,
    ];

    $this->postJson(route('admin.magic_ai.system_prompt.store'), $payload)
        ->assertOk();

    $this->putJson(route('admin.magic_ai.system_prompt.update'), ['id' => $prompt->id] + $payload)
        ->assertOk();

    expect($prompt->fresh()->max_tokens)->toBe(2000);
});
