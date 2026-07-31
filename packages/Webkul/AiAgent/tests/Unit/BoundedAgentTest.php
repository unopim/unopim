<?php

use Laravel\Ai\AnonymousAgent;
use Webkul\AiAgent\Chat\BoundedAgent;

it('exposes the configured step cap through maxSteps()', function () {
    $agent = new BoundedAgent('instructions', [], [], 5);

    expect($agent->maxSteps())->toBe(5);
});

it('extends AnonymousAgent so laravel/ai reads the maxSteps() seam', function () {
    $agent = new BoundedAgent('instructions', [], [], 7);

    // laravel/ai's TextGenerationOptions::forAgent() honours maxSteps() only
    // when the agent is a valid Agent with that method present.
    expect($agent)->toBeInstanceOf(AnonymousAgent::class);
    expect(method_exists($agent, 'maxSteps'))->toBeTrue();
});

it('passes instructions, messages and tools through to the base agent', function () {
    $messages = ['m'];
    $tools = ['t'];

    $agent = new BoundedAgent('you are a test agent', $messages, $tools, 3);

    expect($agent->instructions())->toBe('you are a test agent');
    expect($agent->messages())->toBe($messages);
    expect($agent->tools())->toBe($tools);
});

it('wires the enforced step cap into the agent runner', function () {
    $source = file_get_contents(
        base_path('packages/Webkul/AiAgent/src/Chat/AgentRunner.php')
    );

    expect($source)->toContain('new BoundedAgent(');
    expect($source)->toContain('maxSteps: $this->resolveMaxSteps()');
    expect($source)->not->toContain('new AnonymousAgent(');
});
