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
    config(['ai-agent.history.enabled' => true]);
    Http::preventStrayRequests();
    $descriptor = ['type' => 'example.lookup', 'id' => 'existing-result'];
    $stream = new StreamableAgentResponse('fixture', function () use ($descriptor) {
        yield new ToolResultEvent('tool-event', new ToolResult(
            'tool-call', 'example_lookup', [], json_encode(['presentation' => $descriptor, 'mutated' => false]),
        ), true, null, time());
        yield new TextDelta('text-event', 'reply', 'Existing result ready.', time());
    }, new Meta('openai', 'fixture-model'));
    if ($observerFails) {
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

    // Exercise real owner-scoped persistence independently of browser storage.
    $this->actingAs($admin, 'admin');
    $saved = $this->postJson(route('ai-agent.conversations.store'), [
        'conversation_id' => 'history-fixture', 'title' => 'Saved result',
        'messages'        => [['role' => 'assistant', 'content' => 'Result available', 'message_id' => 'reply-1', 'presentation' => $receipt['presentation']]],
    ])->assertOk();
    $this->getJson(route('ai-agent.conversations.show', $saved->json('id')))
        ->assertOk()->assertJsonPath('messages.0.presentation', $descriptor);
    Http::assertNothingSent();
})->with(['observer disabled' => false, 'observer throws after result' => true]);
