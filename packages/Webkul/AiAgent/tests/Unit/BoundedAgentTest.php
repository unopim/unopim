<?php

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
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

it('repairs a malformed tool name instead of aborting the turn', function () {
    $tool = new class implements Tool
    {
        public function name(): string
        {
            return 'list_attributes';
        }

        public function description(): string
        {
            return 'List attributes';
        }

        public function handle(Request $request): string
        {
            return 'sku, name';
        }

        public function schema(JsonSchema $schema): array
        {
            return [];
        }
    };

    BoundedAgent::fake([
        new ToolCall('call_1', 'list_attributes<|channel|>commentary', []),
        new ToolCall('call_2', 'list_attributes', []),
        'Attributes: sku, name',
    ]);

    $response = (new BoundedAgent('instructions', [], [$tool], 5))->prompt('list attributes');

    expect($response->text)->toBe('Attributes: sku, name');
    expect($response->toolResults->first()->result)
        ->toContain("Tool 'list_attributes<|channel|>commentary' does not exist")
        ->toContain('list_attributes');
});
