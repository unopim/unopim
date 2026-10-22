<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\ToolCall as ToolCallData;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

/**
 * An AgentRunner whose agent replays the given stream events.
 */
function replayingRunner(array $events): AgentRunner
{
    $stream = new StreamableAgentResponse('fixture', function () use ($events) {
        yield from $events;
    }, new Meta('openai', 'fixture-model'));

    $agent = Mockery::mock(AnonymousAgent::class);
    $agent->shouldReceive('stream')->andReturn($stream);

    return new class(resolve(ToolRegistry::class), $agent) extends AgentRunner
    {
        public function __construct(ToolRegistry $registry, private AnonymousAgent $fixture)
        {
            parent::__construct($registry);
        }

        public function systemPrompt(ChatContext $context): string
        {
            return $this->buildSystemPrompt($context);
        }

        protected function buildAgent(ChatContext $context): AnonymousAgent
        {
            return $this->fixture;
        }

        protected function disableOutputBuffering(): void {}
    };
}

function generateContext(array $history = []): ChatContext
{
    return new ChatContext(
        message: 'Show the product', history: $history, productId: null, productSku: null, productName: null,
        locale: 'en_US', channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'api_key' => 'fixture-only']),
        model: 'fixture-model', user: Admin::factory()->create(),
    );
}

it('emits progress events in order and returns the result for every transport', function () {
    Http::preventStrayRequests();
    $runner = replayingRunner([
        new ToolCall('call-event', new ToolCallData('call-1', 'search_products', []), time()),
        new ToolResultEvent('result-event', new ToolResult('call-1', 'search_products', [], json_encode(['product_url' => '/p/1'])), true, null, time()),
        new TextDelta('text-1', 'reply', 'Here ', time()),
        new TextDelta('text-2', 'reply', 'it is.', time()),
    ]);

    $events = [];
    $result = $runner->generate(generateContext(), function (string $event, array $data) use (&$events) {
        $events[] = [$event, $data];
    });

    expect(array_column($events, 0))->toBe(['status', 'tool_call', 'tool_result', 'status', 'text_delta', 'text_delta'])
        ->and($events[1][1])->toBe(['step' => 1, 'tool' => 'search_products'])
        ->and($events[2][1])->toBe(['product_url' => '/p/1'])
        ->and($result['reply'])->toBe('Here it is.')
        ->and($result['data']['steps'])->toBe(1)
        ->and($result['product_url'])->toBe('/p/1');
    Http::assertNothingSent();
});

it('sends the checked reply in the SSE complete event so a replaced reply wins over the streamed text', function () {
    $missing = (int) DB::table('products')->max('id') + 100;
    $runner = replayingRunner([
        new TextDelta('text-1', 'reply', '[Ghost]('.route('admin.catalog.products.edit', ['id' => $missing]).')', time()),
    ]);

    ob_start();
    $runner->runStreaming(generateContext())->sendContent();
    $output = (string) ob_get_clean();

    preg_match('/event: complete\ndata: (.+)\n/', $output, $match);

    expect(json_decode($match[1] ?? '{}', true)['reply'] ?? null)
        ->toBe(trans('ai-agent::app.common.ungrounded-product-reply'));
});

it('fills the app url into the image instructions of the system prompt', function () {
    config(['app.url' => 'https://pim.example.test/']);

    $prompt = replayingRunner([])->systemPrompt(generateContext());

    expect($prompt)->not->toContain('{app_url}')
        ->toContain('https://pim.example.test/storage/{that exact path}');
});
