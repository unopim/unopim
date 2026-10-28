<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\MessageDisplay;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools\EstimateTokens;
use Webkul\AiAgent\Services\BulkEnrichmentEstimator;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Http::preventStrayRequests();
    Event::fake();
});

function estimateChatContext(?Admin $user = null): ChatContext
{
    return new ChatContext(
        message: 'Write descriptions for all products',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['label' => 'OpenAI', 'provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        user: $user ?? Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]),
    );
}

function runEstimateTool(int $limit, ?Admin $user = null): array
{
    $tool = resolve(EstimateTokens::class)->register(estimateChatContext($user));

    return json_decode((string) $tool->handle(new Request(['filter_by' => 'all', 'limit' => $limit])), true);
}

function estimateToolResult(array $payload): ToolResult
{
    return new ToolResult(id: 'call_1', name: 'estimate_tokens', arguments: [], result: json_encode($payload));
}

function validEstimate(): array
{
    return ['products' => 40, 'sampled' => 20, 'sampled_estimate' => true, 'estimated_input_tokens' => 18000, 'max_output_tokens' => 81920];
}

it('attaches a confirmation estimate when the bulk request covers more than the threshold', function () {
    Product::factory()->count(BulkEnrichmentEstimator::BULK_THRESHOLD + 5)->create();

    $payload = runEstimateTool(BulkEnrichmentEstimator::BULK_THRESHOLD + 5);

    expect($payload['token_estimate'] ?? null)->toBeArray()
        ->and($payload['token_estimate']['products'])->toBe(BulkEnrichmentEstimator::BULK_THRESHOLD + 5)
        ->and($payload['token_estimate']['estimated_input_tokens'])->toBeGreaterThan(0)
        ->and(BulkEnrichmentEstimator::normalize($payload['token_estimate']))->toBe($payload['token_estimate']);

    Http::assertNothingSent();
});

it('does not attach a confirmation estimate for a small request', function () {
    Product::factory()->count(3)->create();

    $payload = runEstimateTool(3);

    expect($payload)->toHaveKey('estimated_input_tokens')
        ->and($payload)->not->toHaveKey('token_estimate');
});

it('denies the estimate without catalog access', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);
    $user = Admin::factory()->create(['role_id' => $role->id]);

    $payload = runEstimateTool(50, $user);

    expect($payload['error'])->toContain('Permission denied')
        ->and($payload)->not->toHaveKey('token_estimate');
});

it('carries the estimate into the blocking chat reply payload', function () {
    $response = (new AgentResponse('invocation', 'Shall I proceed?', new TextUsage, new Meta))
        ->withToolCallsAndResults(collect(), collect([estimateToolResult(['token_estimate' => validEstimate()])]));

    $runner = new AgentRunner(app(ToolRegistry::class));
    $result = [];

    (new ReflectionMethod($runner, 'extractActionResults'))->invokeArgs($runner, [$response, &$result]);

    expect($result['token_estimate'])->toBe(validEstimate());
});

it('carries the estimate into the streamed chat reply payload', function () {
    $stream = (object) ['events' => [(object) ['toolResult' => estimateToolResult(['token_estimate' => validEstimate()])]]];

    $runner = new AgentRunner(app(ToolRegistry::class));
    $result = [];

    (new ReflectionMethod($runner, 'extractStreamActionResults'))->invokeArgs($runner, [$stream, &$result]);

    expect($result['token_estimate'])->toBe(validEstimate());
});

it('ignores a malformed estimate in a tool payload', function () {
    $runner = new AgentRunner(app(ToolRegistry::class));
    $result = [];

    (new ReflectionMethod($runner, 'mergeActionPayload'))->invokeArgs($runner, [['token_estimate' => ['products' => 'many']], &$result]);

    expect($result)->not->toHaveKey('token_estimate');
});

it('keeps a valid estimate when a conversation message is saved', function () {
    $display = app(MessageDisplay::class)->normalize(['role' => 'assistant', 'content' => 'Shall I proceed?', 'token_estimate' => validEstimate()]);

    expect($display['token_estimate'])->toBe(validEstimate());
});

it('rejects a malformed estimate when a conversation message is saved', function () {
    app(MessageDisplay::class)->normalize(['role' => 'assistant', 'content' => 'Shall I proceed?', 'token_estimate' => ['products' => '<b>']]);
})->throws(ValidationException::class);

it('renders the estimate card above the confirmation buttons in the chat widget', function () {
    $template = (string) file_get_contents(base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php'));

    $card = strpos($template, 'v-if="msg.token_estimate && needsConfirmation(msg, idx)"');
    $buttons = strpos($template, 'v-if="needsConfirmation(msg, idx)" class="flex gap-2 mt-1"');

    expect($card)->not->toBeFalse()
        ->and($buttons)->not->toBeFalse()
        ->and($card)->toBeLessThan($buttons)
        ->and($template)->toContain("tokenEstimateTitle: `@lang('ai-agent::app.widget.token-estimate.title')`")
        ->and($template)->toContain('if (msg.token_estimate) return true;');

    foreach (['title', 'products', 'input-tokens', 'output-budget', 'approximate', 'sampled', 'no-cost'] as $key) {
        expect(trans('ai-agent::app.widget.token-estimate.'.$key))->not->toBe('ai-agent::app.widget.token-estimate.'.$key);
    }
});
