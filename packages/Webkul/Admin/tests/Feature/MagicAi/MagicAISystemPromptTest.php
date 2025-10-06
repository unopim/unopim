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
