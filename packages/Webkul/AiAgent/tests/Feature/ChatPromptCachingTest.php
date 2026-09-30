<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\Core\Models\CoreConfig;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

beforeEach(function () {
    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.magic_ai.agentic_pim.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );

    MagicAIPlatform::query()->delete();

    $this->admin = Admin::factory()->create();

    $this->actingAs($this->admin, 'admin');
});

function cachingChatContext(string $provider, string $model, ?Admin $user = null): ChatContext
{
    $platform = MagicAIPlatform::create([
        'label'      => ucfirst($provider),
        'provider'   => $provider,
        'api_key'    => 'test-key',
        'models'     => $model,
        'status'     => true,
        'is_default' => true,
    ]);

    return new ChatContext(
        message: 'List the catalog summary',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: $platform,
        model: $model,
        user: $user,
    );
}

function fakeAnthropicReply(): void
{
    Http::fake(['*' => Http::response([
        'id'          => 'msg_1',
        'type'        => 'message',
        'role'        => 'assistant',
        'model'       => 'claude-sonnet-4-5',
        'content'     => [['type' => 'text', 'text' => 'Done.']],
        'stop_reason' => 'end_turn',
        'usage'       => [
            'input_tokens'                => 40,
            'output_tokens'               => 10,
            'cache_creation_input_tokens' => 0,
            'cache_read_input_tokens'     => 1500,
        ],
    ])]);
}

describe('Agentic chat prompt caching (Issue #421)', function () {

    it('marks the anthropic chat instructions and tool definitions with cache_control', function () {
        fakeAnthropicReply();

        app(AgentRunner::class)->run(cachingChatContext('anthropic', 'claude-sonnet-4-5', $this->admin));

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            $system = $body['system'] ?? [];
            $tools = $body['tools'] ?? [];

            return is_array($system)
                && ($system[array_key_last($system)]['cache_control']['type'] ?? null) === 'ephemeral'
                && $tools !== []
                && ($tools[array_key_last($tools)]['cache_control']['type'] ?? null) === 'ephemeral';
        });
    });

    it('reports the cached input tokens of a chat turn separately', function () {
        fakeAnthropicReply();

        $result = app(AgentRunner::class)->run(cachingChatContext('anthropic', 'claude-sonnet-4-5', $this->admin));

        expect($result['data']['tokens_used'])->toBe(50)
            ->and($result['data']['cached_tokens'])->toBe(1500);
    });

    it('does not add cache_control to openai chat requests', function () {
        Http::fake(['*' => Http::response([
            'id'     => 'resp_1',
            'model'  => 'gpt-4o',
            'status' => 'completed',
            'output' => [[
                'type'    => 'message',
                'role'    => 'assistant',
                'content' => [['type' => 'output_text', 'text' => 'Done.']],
            ]],
            'usage' => [
                'input_tokens'         => 1200,
                'output_tokens'        => 10,
                'input_tokens_details' => ['cached_tokens' => 1024],
            ],
        ])]);

        $result = app(AgentRunner::class)->run(cachingChatContext('openai', 'gpt-4o', $this->admin));

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->body(), 'cache_control'));

        expect($result['data']['cached_tokens'])->toBe(1024);
    });

    it('records the chat turn cached tokens in the daily usage row', function () {
        $platform = MagicAIPlatform::create([
            'label'      => 'Anthropic',
            'provider'   => 'anthropic',
            'api_key'    => 'test-key',
            'models'     => 'claude-sonnet-4-5',
            'status'     => true,
            'is_default' => true,
        ]);

        $runner = new class(app(ToolRegistry::class)) extends AgentRunner
        {
            public function run(ChatContext $context): array
            {
                return [
                    'reply'  => 'ok',
                    'action' => 'agent_response',
                    'data'   => ['steps' => 1, 'tokens_used' => 300, 'cached_tokens' => 200],
                ];
            }
        };

        app()->instance(AgentRunner::class, $runner);

        DB::table('ai_agent_token_usage')->where('user_id', $this->admin->id)->delete();

        $this->post(route('ai-agent.chat.send'), [
            'message'     => 'Hello',
            'platform_id' => $platform->id,
            'model'       => 'claude-sonnet-4-5',
        ])->assertOk();

        $row = DB::table('ai_agent_token_usage')
            ->where('user_id', $this->admin->id)
            ->where('usage_date', now()->toDateString())
            ->first();

        expect((int) $row->tokens_used)->toBe(300)
            ->and((int) $row->cached_tokens)->toBe(200);
    });
});
