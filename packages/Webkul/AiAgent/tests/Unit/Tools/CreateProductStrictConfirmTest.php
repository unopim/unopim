<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\CreateProduct;
use Webkul\Core\RequestMemo;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('creates the product right away in strict confirm mode instead of leaving it in a pending changeset', function () {
    Queue::fake();

    $admin = Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]);
    $this->actingAs($admin, 'admin');

    DB::table('core_config')->where('code', 'general.magic_ai.agentic_pim.approval_mode')->delete();
    DB::table('core_config')->insert([
        'code'       => 'general.magic_ai.agentic_pim.approval_mode',
        'value'      => 'review',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    app(RequestMemo::class)->forget('core_config.');

    $sku = 'STRICT-CONFIRM-'.random_int(10000, 99999);

    $context = new ChatContext(
        message: 'create a product',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        user: $admin,
    );

    $result = json_decode((string) app(CreateProduct::class)
        ->register($context)
        ->handle(new Request(['sku' => $sku, 'name' => 'Strict Confirm Product', 'family' => 'default'])), true);

    expect($result['result']['created'] ?? null)->toBeTrue();
    expect($result['result'])->not->toHaveKey('queued');
    expect(Product::query()->where('sku', $sku)->exists())->toBeTrue();
    expect(DB::table('ai_agent_changesets')->where('status', 'pending')->where('description', 'like', '%Strict Confirm Product%')->exists())->toBeFalse();
});
