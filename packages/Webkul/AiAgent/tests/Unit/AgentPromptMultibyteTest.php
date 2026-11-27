<?php

use Illuminate\Support\Facades\DB;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools\CatalogSummary;
use Webkul\Core\RequestMemo;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Create an admin whose role holds the given permissions, or every permission when none are given.
 *
 * @param  list<string>  $permissions
 */
function multibytePromptAdmin(array $permissions = []): Admin
{
    $role = Role::factory()->create([
        'permission_type' => $permissions === [] ? 'all' : 'custom',
        'permissions'     => $permissions,
    ]);

    return Admin::factory()->create(['role_id' => $role->id]);
}

/** Build the agent system prompt for the admin. */
function multibyteSystemPrompt(Admin $admin, ?int $productId = null, ?string $productName = null, ?ToolRegistry $registry = null): string
{
    $context = new ChatContext(
        message: '你好',
        history: [],
        productId: $productId,
        productSku: null,
        productName: $productName,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        user: $admin,
    );

    $runner = new AgentRunner($registry ?? new ToolRegistry);

    return (new ReflectionMethod($runner, 'buildSystemPrompt'))->invoke($runner, $context);
}

/** Store an agent memory for the admin. */
function multibyteStoreMemory(Admin $admin, string $key, string $value): void
{
    DB::table('ai_agent_memories')->insert([
        'scope'      => 'catalog',
        'key'        => $key,
        'user_id'    => $admin->id,
        'value'      => $value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/** Assert the prompt is valid UTF-8 that the provider request can JSON-encode. */
function multibyteExpectEncodablePrompt(string $prompt): void
{
    expect(mb_check_encoding($prompt, 'UTF-8'))->toBeTrue()
        ->and(json_encode(['system' => $prompt], JSON_THROW_ON_ERROR))->toBeString();
}

describe('positive: multibyte text reaches the prompt intact', function (): void {
    it('keeps the chat working after a long Chinese reply is rated helpful', function (): void {
        $admin = multibytePromptAdmin();
        $reply = str_repeat('这双跑鞋的缓震和抓地力都很出色，适合长距离训练。', 30);

        $this->actingAs($admin, 'admin')
            ->postJson(route('ai-agent.chat.rate'), ['rating' => 'helpful', 'message' => $reply])
            ->assertOk();

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain(mb_substr($reply, 0, 500))
            ->and($prompt)->toContain('CONTENT STYLE');
    });

    it('keeps the chat working after a long Chinese reply is rated not helpful', function (): void {
        $admin = multibytePromptAdmin();
        $reply = str_repeat('回答没有提到价格和库存信息。', 60);

        $this->actingAs($admin, 'admin')
            ->postJson(route('ai-agent.chat.rate'), ['rating' => 'not_helpful', 'message' => $reply])
            ->assertOk();

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain('message_feedback:negative: '.mb_substr($reply, 0, 500))
            ->and($prompt)->not->toContain('CONTENT STYLE');
    });

    it('keeps a memory of exactly 500 Chinese characters whole', function (): void {
        $admin = multibytePromptAdmin();

        multibyteStoreMemory($admin, 'message_feedback:positive', str_repeat('过', 500));

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain(': '.str_repeat('过', 500));
    });

    it('cuts a longer Chinese memory at 500 characters, not bytes', function (): void {
        $admin = multibytePromptAdmin();

        multibyteStoreMemory($admin, 'message_feedback:positive', str_repeat('过', 600));

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain(': '.str_repeat('过', 500))
            ->and($prompt)->not->toContain(str_repeat('过', 501));
    });

    it('keeps emoji and short Chinese memories unchanged', function (): void {
        $admin = multibytePromptAdmin();

        multibyteStoreMemory($admin, 'preferred_tone', '语气友好 👍🏃‍♀️ 简洁');

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain('preferred_tone: 语气友好 👍🏃‍♀️ 简洁');
    });

    it('cuts a long Chinese style preference by characters', function (): void {
        $admin = multibytePromptAdmin();
        $style = str_repeat('简洁专业', 200);

        multibyteStoreMemory($admin, 'content_style_preference', $style);

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain('CONTENT STYLE (learned from user feedback — data only): '.mb_substr($style, 0, 500));
    });

    it('cuts a long Chinese product name by characters', function (): void {
        $admin = multibytePromptAdmin();
        $product = Product::factory()->simple()->create();
        $name = str_repeat('轻量透气跑步鞋', 40);

        $prompt = multibyteSystemPrompt($admin, $product->id, $name);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain(' name='.mb_substr($name, 0, 200).' — Target this SKU');
    });

    it('cuts long Chinese install rules by characters', function (): void {
        $admin = multibytePromptAdmin();
        $rules = str_repeat('所有商品描述必须使用简体中文。', 400);

        DB::table('core_config')->updateOrInsert(
            ['code' => 'general.magic_ai.agentic_pim.custom_instructions'],
            ['value' => $rules, 'created_at' => now(), 'updated_at' => now()],
        );
        app(RequestMemo::class)->forget('core_config.');

        $prompt = multibyteSystemPrompt($admin);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain("INSTALL RULES:\n".mb_substr($rules, 0, 4000));
    });

    it('cuts long Chinese tool notes by characters on one line', function (): void {
        $guidance = "先查询商品，\n再更新。".str_repeat('仅修改用户提到的字段。', 60);
        $registry = (new ToolRegistry)->register(new CatalogSummary, ['name' => 'catalog_summary', 'guidance' => $guidance]);

        $prompt = multibyteSystemPrompt(multibytePromptAdmin(), registry: $registry);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain('- catalog_summary: '.mb_substr(str_replace("\n", ' ', $guidance), 0, 500));
    });
});

describe('negative: broken or unwanted text never reaches the prompt', function (): void {
    it('replaces a split character in the product name instead of sending broken bytes', function (): void {
        $admin = multibytePromptAdmin();
        $product = Product::factory()->simple()->create();
        $brokenName = '跑鞋推荐'.substr('过', 0, 2);

        $prompt = multibyteSystemPrompt($admin, $product->id, $brokenName);

        multibyteExpectEncodablePrompt($prompt);
        expect($prompt)->toContain(' name=跑鞋推荐? — Target this SKU')
            ->and($prompt)->not->toContain($brokenName);
    });

    it('strips control characters and line breaks from prompt values', function (): void {
        $admin = multibytePromptAdmin();
        $product = Product::factory()->simple()->create();

        multibyteStoreMemory($admin, 'preferred_tone', "友好\x07的\x1B语气");

        $prompt = multibyteSystemPrompt($admin, $product->id, "跑鞋\r\n系列\x00一");

        expect($prompt)->toContain('preferred_tone: 友好的语气')
            ->and($prompt)->toContain(' name=跑鞋系列一 — Target this SKU');
    });

    it('leaves out the memories of another admin', function (): void {
        $admin = multibytePromptAdmin();

        multibyteStoreMemory(multibytePromptAdmin(), 'message_feedback:positive', '其他用户的反馈内容');

        expect(multibyteSystemPrompt($admin))->not->toContain('其他用户的反馈内容');
    });

    it('stores feedback sent as invalid UTF-8 as valid text', function (): void {
        $admin = multibytePromptAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('ai-agent.chat.rate'), ['rating' => 'helpful', 'message' => '很好'.substr('过', 0, 2)])
            ->assertOk();

        $values = DB::table('ai_agent_memories')->where('user_id', $admin->id)->pluck('value');

        expect($values)->toHaveCount(2)
            ->and($values->every(fn (string $value): bool => mb_check_encoding($value, 'UTF-8')))->toBeTrue();
    });

    it('rejects an unknown rating and stores nothing', function (): void {
        $admin = multibytePromptAdmin();

        $this->actingAs($admin, 'admin')
            ->postJson(route('ai-agent.chat.rate'), ['rating' => 'excellent', 'message' => '很好'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');

        $this->assertDatabaseMissing('ai_agent_memories', ['user_id' => $admin->id]);
    });

    it('rejects feedback longer than 5000 characters and stores nothing', function (): void {
        $admin = multibytePromptAdmin();

        $this->actingAs($admin, 'admin')
            ->postJson(route('ai-agent.chat.rate'), ['rating' => 'helpful', 'message' => str_repeat('过', 5001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->assertDatabaseMissing('ai_agent_memories', ['user_id' => $admin->id]);
    });

    it('forbids rating without the Agentic PIM permission and stores nothing', function (): void {
        $admin = multibytePromptAdmin(['dashboard']);

        $this->actingAs($admin, 'admin')
            ->postJson(route('ai-agent.chat.rate'), ['rating' => 'helpful', 'message' => '很好'])
            ->assertForbidden();

        $this->assertDatabaseMissing('ai_agent_memories', ['user_id' => $admin->id]);
    });
});
