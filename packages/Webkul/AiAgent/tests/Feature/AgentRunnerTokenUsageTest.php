<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

beforeEach(function (): void {
    MagicAIPlatform::query()->delete();

    $this->platform = MagicAIPlatform::create([
        'label'      => 'OpenAI',
        'provider'   => 'openai',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4o-mini',
        'status'     => true,
        'is_default' => true,
    ]);

    $this->admin = Admin::factory()->create();
});

/**
 * Usage block as the OpenAI Responses API reports it: 1,000 of the 1,200
 * input tokens were served from the prompt cache.
 *
 * @return array<string, mixed>
 */
function openAiUsage(): array
{
    return [
        'input_tokens'          => 1200,
        'output_tokens'         => 345,
        'input_tokens_details'  => ['cached_tokens' => 1000],
        'output_tokens_details' => ['reasoning_tokens' => 0],
    ];
}

/**
 * @return array<string, mixed>
 */
function openAiCompletedResponse(): array
{
    return [
        'id'     => 'resp_1',
        'model'  => 'gpt-4o-mini',
        'status' => 'completed',
        'output' => [[
            'type'    => 'message',
            'role'    => 'assistant',
            'content' => [['type' => 'output_text', 'text' => 'Done.']],
        ]],
        'usage' => openAiUsage(),
    ];
}

function tokenUsageContext(MagicAIPlatform $platform, Admin $admin): ChatContext
{
    return new ChatContext(
        message: 'How many products are enabled?',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: $platform,
        model: 'gpt-4o-mini',
        user: $admin,
    );
}

it('reports input plus output tokens, cached input included, for a blocking turn', function (): void {
    Http::fake(['*' => Http::response(openAiCompletedResponse())]);

    $result = resolve(AgentRunner::class)->run(tokenUsageContext($this->platform, $this->admin));

    expect($result['data']['tokens_used'])->toBe(1545);
});

it('reports and records input plus output tokens for a streamed turn', function (): void {
    $events = [
        ['type' => 'response.created', 'response' => ['id' => 'resp_1', 'model' => 'gpt-4o-mini']],
        ['type' => 'response.output_text.delta', 'item_id' => 'msg_1', 'delta' => 'Done.'],
        ['type' => 'response.completed', 'response' => openAiCompletedResponse()],
    ];

    $body = collect($events)->map(fn (array $event): string => 'data: '.json_encode($event)."\n\n")->implode('');

    Http::fake(['*' => Http::response($body, 200, ['Content-Type' => 'text/event-stream'])]);

    $runner = new class(resolve(ToolRegistry::class)) extends AgentRunner
    {
        protected function disableOutputBuffering(): void {}
    };

    ob_start();
    $runner->runStreaming(tokenUsageContext($this->platform, $this->admin))->sendContent();
    $output = (string) ob_get_clean();

    expect($output)->toContain('"tokens_used":1545');

    expect(DB::table('ai_agent_token_usage')->where('user_id', $this->admin->id)->value('tokens_used'))
        ->toBe(1545);
});
