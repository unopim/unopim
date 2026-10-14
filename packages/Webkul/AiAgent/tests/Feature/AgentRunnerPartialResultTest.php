<?php

use Illuminate\Support\Facades\Http;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

it('delivers completed tool references before an observer can interrupt the stream', function (bool $observerFails) {
    Http::preventStrayRequests();
    $descriptor = ['type' => 'example.lookup', 'id' => 'existing-result'];
    $stream = new StreamableAgentResponse('fixture', function () use ($descriptor) {
        yield new ToolResultEvent('tool-event', new ToolResult(
            'tool-call', 'example_lookup', [], json_encode(['presentation' => $descriptor, 'mutated' => false]),
        ), true, null, time());
        yield new TextDelta('text-event', 'reply', 'Existing result ready.', time());
    }, new Meta('openai', 'fixture-model'));
    if ($observerFails) {
        // Debugbar's incompatible usage collector throws here, after tool work
        // completed. Keep the regression independent of that optional package.
        $stream->then(fn () => throw new RuntimeException('Completion observer failed'));
    }
    $agent = Mockery::mock(AnonymousAgent::class);
    $agent->shouldReceive('stream')->once()->andReturn($stream);
    $runner = new class(resolve(ToolRegistry::class), $agent) extends AgentRunner
    {
        public function __construct(ToolRegistry $registry, private AnonymousAgent $fixture)
        {
            parent::__construct($registry);
        }

        protected function buildAgent(ChatContext $context): AnonymousAgent
        {
            return $this->fixture;
        }

        protected function disableOutputBuffering(): void {}
    };
    $admin = Admin::factory()->create();
    $context = new ChatContext(
        message: 'Show an existing result', history: [], productId: null, productSku: null, productName: null,
        locale: 'en_US', channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'api_key' => 'fixture-only']),
        model: 'fixture-model', user: $admin,
    );
    ob_start();
    $runner->runStreaming($context)->sendContent();
    $output = (string) ob_get_clean();
    preg_match('/event: tool_result\ndata: (.+)\n/', $output, $match);
    $this->assertNotEmpty($match, $output);
    $receipt = json_decode($match[1], true);
    expect($receipt['presentation'])->toBe($descriptor);
    if ($observerFails) {
        expect($output)->toContain('event: error')->not->toContain('event: complete');
        expect(strpos($output, 'event: tool_result'))->toBeLessThan(strpos($output, 'event: error'));
    } else {
        expect($output)->toContain('event: complete')->not->toContain('event: error');
    }

    Http::assertNothingSent();
})->with(['observer disabled' => false, 'observer throws after result' => true]);
