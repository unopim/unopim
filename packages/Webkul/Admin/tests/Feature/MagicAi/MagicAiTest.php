<?php

it('returns AI models', function () {
    $this->loginAsAdmin();

    $response = $this->getJson(route('admin.magic_ai.model'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'models',
            'message',
        ])
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.products.index.magic-ai-validate-success'),
        ]);
});

it('returns available AI models', function () {
    $this->loginAsAdmin();

    $response = $this->getJson(route('admin.magic_ai.available_model'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'models',
        ]);
});

it('returns suggestion values for attributes or category fields', function () {
    $this->loginAsAdmin();

    $query = 'test';
    $entityName = 'attribute';

    $response = $this->getJson(route('admin.magic_ai.suggestion_values', [
        'query'       => $query,
        'entity_name' => $entityName,
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['code', 'name'],
        ]);
});

it('returns default translated prompts', function () {
    $this->loginAsAdmin();
    $response = $this->getJson(route('admin.magic_ai.default_prompt'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'prompts' => [
                '*' => ['prompt', 'title'],
            ],
        ]);
});
