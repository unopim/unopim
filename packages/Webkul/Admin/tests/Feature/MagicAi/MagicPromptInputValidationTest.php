<?php

use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;

beforeEach(function (): void {
    $this->loginAsAdmin();
});

function validPromptPayload(array $override = []): array
{
    return array_merge([
        'title'   => 'Short description',
        'prompt'  => 'Write a short description for @name',
        'type'    => 'product',
        'purpose' => 'text_generation',
        'tone'    => MagicAISystemPrompt::query()->value('id'),
    ], $override);
}

function postPrompt(array $payload)
{
    return test()->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.magic_ai.prompt.store'), $payload);
}

it('rejects a prompt with an out-of-range or invalid field with a 422 on create', function (string $field, mixed $value): void {
    postPrompt(validPromptPayload([$field => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'title too long'    => ['title', str_repeat('a', 256)],
    'title not text'    => ['title', ['x']],
    'prompt too long'   => ['prompt', str_repeat('a', 10001)],
    'type not allowed'  => ['type', 'banana'],
    'purpose not valid' => ['purpose', 'banana'],
    'tone missing row'  => ['tone', 999999999],
    'tone not numeric'  => ['tone', 'abc'],
]);

it('rejects the same invalid input on update', function (): void {
    $prompt = MagicPrompt::factory()->create();

    test()->withHeaders(['Accept' => 'application/json'])
        ->put(route('admin.magic_ai.prompt.update'), validPromptPayload(['id' => $prompt->id, 'title' => str_repeat('a', 256)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('accepts values at the limits', function (): void {
    postPrompt(validPromptPayload([
        'title'  => str_repeat('a', 255),
        'prompt' => str_repeat('a', 10000),
    ]))->assertOk();
});
