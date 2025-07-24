<?php

use Webkul\MagicAI\Models\MagicPrompt;

it('should return the MagicaAiDatagrid', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.magic_ai.prompt.index'))
        ->assertOk();
});

it('should return the MagicPromptGrid as JSON for AJAX requests', function () {
    $this->loginAsAdmin();
    MagicPrompt::factory()->create();
    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.magic_ai.prompt.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);
    $this->assertDatabaseHas($this->getFullTableName(MagicPrompt::class), [
        'id' => $data['records'][0]['id'],
    ]);
});

it('should return data related to id', function () {
    $this->loginAsAdmin();
    $prompt = MagicPrompt::factory()->create();
    $response = $this->get(route('admin.magic_ai.prompt.edit', $prompt->id));
    $response->assertOk();
});

it('should return validation error for title and prompt', function () {
    $this->loginAsAdmin();
    $prompt = MagicPrompt::factory()->make()->toArray();
    unset($prompt['title']);
    unset($prompt['prompt']);

    $this->post(route('admin.magic_ai.prompt.store'), $prompt)
        ->assertRedirect()
        ->assertSessionHasErrors([
            'prompt',
            'title',
        ]);
});

it('should create new row for MagicAiprompt', function () {
    $this->loginAsAdmin();
    $prompt = MagicPrompt::factory()->make()->toArray();
    $this->post(route('admin.magic_ai.prompt.store', $prompt))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.configuration.prompt.message.save-success')]);
    $this->assertDatabaseHas($this->getFullTableName(MagicPrompt::class), [
        'title'  => $prompt['title'],
        'prompt' => $prompt['prompt'],
        'type'   => $prompt['type'],
    ]);
});

it('should update with given data', function () {
    $this->loginAsAdmin();
    $prompt = MagicPrompt::factory()->create();
    $data = [
        'id'     => $prompt->id,
        'title'  => 'hello prompt',
        'prompt' => 'update to @color and @name',
        'type'   => 'product',
    ];

    $this->put(route('admin.magic_ai.prompt.update'), $data)
        ->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.configuration.prompt.message.update-success')]);
    $this->assertDatabaseHas($this->getFullTableName(MagicPrompt::class), $data);
});

it('should delete the prompt with the given id', function () {
    $this->loginAsAdmin();
    $prompt = MagicPrompt::factory()->create();
    $this->delete(route('admin.magic_ai.prompt.delete', $prompt->id))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.configuration.prompt.message.delete-success')]);
    $this->assertDatabaseMissing(
        $this->getFullTableName(MagicPrompt::class),
        [
            'id' => $prompt->id,
        ]
    );
});
