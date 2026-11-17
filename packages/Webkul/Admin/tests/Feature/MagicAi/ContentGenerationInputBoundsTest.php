<?php

use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Responses\GeneratedContent;

beforeEach(function () {
    $this->loginAsAdmin();
});

/**
 * Bind a provider mock that must never be reached: invalid input has to be rejected with a
 * 422 before the provider is resolved or called.
 */
function providerThatMustNotBeCalled(): void
{
    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldNotReceive('useDefault', 'setPlatformId', 'setModel', 'setPrompt', 'askResult');

    app()->instance('magic_ai', $mock);
}

dataset('invalidContentInput', [
    'prompt is an array'  => [['model' => 'gpt-4o-mini', 'prompt' => ['a', 'b']], 'prompt'],
    'prompt is an object' => [['model' => 'gpt-4o-mini', 'prompt' => ['x' => 'y']], 'prompt'],
    'prompt is too long'  => [['model' => 'gpt-4o-mini', 'prompt' => str_repeat('a', 10001)], 'prompt'],
    'model is an array'   => [['model' => ['gpt'], 'prompt' => 'Describe a shoe.'], 'model'],
    'model is too long'   => [['model' => str_repeat('m', 101), 'prompt' => 'Describe a shoe.'], 'model'],
]);

it('rejects invalid prompt and model input with a 422 before calling the provider', function (array $payload, string $field) {
    providerThatMustNotBeCalled();

    $this->postJson(route('admin.magic_ai.content'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with('invalidContentInput');

it('still accepts a prompt at the maximum length', function () {
    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault', 'setPlatformId', 'setModel', 'setTemperature', 'setMaxTokens', 'setSystemPrompt', 'setPrompt')->andReturnSelf();
    $mock->shouldReceive('askResult')->once()->andReturn(new GeneratedContent('ok'));
    app()->instance('magic_ai', $mock);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => str_repeat('a', 10000),
    ])->assertOk();
});
