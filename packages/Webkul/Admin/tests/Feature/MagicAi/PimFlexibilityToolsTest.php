<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\CreateProduct;
use Webkul\AiAgent\Chat\Tools\RecallMemory;
use Webkul\AiAgent\Chat\Tools\RememberFact;
use Webkul\AiAgent\Http\Controllers\ChatController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

it('creates the product in the attribute family passed via the family param', function () {
    $admin = $this->loginAsAdmin();

    Queue::fake();
    setPimFlexCoreConfig('general.magic_ai.agentic_pim.approval_mode', 'direct');

    $suffix = random_int(10000, 99999);
    $family = createPimFlexFamily('pimflex_fam_'.$suffix, ['sku', 'status']);
    $sku = 'PIMFLEX-FAM-'.$suffix;

    $result = decodePimFlexToolResult(
        app(CreateProduct::class)
            ->register(buildPimFlexChatContext($admin))
            ->handle(new Request(['sku' => $sku, 'name' => 'PimFlex Product '.$suffix, 'family' => $family->code]))
    );

    expect($result['result']['created'] ?? null)->toBeTrue();

    $product = Product::query()->where('sku', $sku)->first();

    expect($product)->not->toBeNull();
    expect($product->attribute_family_id)->toBe($family->id);
});

it('returns an error listing available families for an unknown family code', function () {
    $admin = $this->loginAsAdmin();

    setPimFlexCoreConfig('general.magic_ai.agentic_pim.approval_mode', 'direct');

    $suffix = random_int(10000, 99999);

    $result = decodePimFlexToolResult(
        app(CreateProduct::class)
            ->register(buildPimFlexChatContext($admin))
            ->handle(new Request(['name' => 'Ghost Product', 'family' => 'missing_family_'.$suffix]))
    );

    expect($result)->toHaveKey('error');
    expect($result['error'])->toContain('missing_family_'.$suffix);
    expect($result['available_families'])->toBeArray()->not->toBeEmpty();
    expect(count($result['available_families']))->toBeLessThanOrEqual(20);

    $malformed = decodePimFlexToolResult(
        app(CreateProduct::class)
            ->register(buildPimFlexChatContext($admin))
            ->handle(new Request(['name' => 'Ghost Product', 'family' => 'bad"family']))
    );

    expect($malformed)->toHaveKey('error');
});

it('does not default price or cost when the family lacks those attributes', function () {
    $admin = $this->loginAsAdmin();

    Queue::fake();
    setPimFlexCoreConfig('general.magic_ai.agentic_pim.approval_mode', 'direct');

    $suffix = random_int(10000, 99999);
    $family = createPimFlexFamily('pimflex_noprice_'.$suffix, ['sku', 'status']);
    $sku = 'PIMFLEX-NOPRICE-'.$suffix;

    $result = decodePimFlexToolResult(
        app(CreateProduct::class)
            ->register(buildPimFlexChatContext($admin))
            ->handle(new Request(['sku' => $sku, 'name' => 'No Price Product '.$suffix, 'family' => $family->code]))
    );

    expect($result['result']['created'] ?? null)->toBeTrue();

    $values = Product::query()->where('sku', $sku)->first()->values ?? [];

    expect(pimFlexValuesHaveAttribute($values, 'price'))->toBeFalse();
    expect(pimFlexValuesHaveAttribute($values, 'cost'))->toBeFalse();
});

it('still defaults price when the family carries the price attribute', function () {
    $admin = $this->loginAsAdmin();

    Queue::fake();
    setPimFlexCoreConfig('general.magic_ai.agentic_pim.approval_mode', 'direct');

    $suffix = random_int(10000, 99999);
    $family = createPimFlexFamily('pimflex_price_'.$suffix, ['sku', 'status', 'price']);
    $sku = 'PIMFLEX-PRICE-'.$suffix;

    $result = decodePimFlexToolResult(
        app(CreateProduct::class)
            ->register(buildPimFlexChatContext($admin))
            ->handle(new Request(['sku' => $sku, 'name' => 'Priced Product '.$suffix, 'family' => $family->code]))
    );

    expect($result['result']['created'] ?? null)->toBeTrue();

    $values = Product::query()->where('sku', $sku)->first()->values ?? [];

    expect(pimFlexValuesHaveAttribute($values, 'price'))->toBeTrue();
});

it('stores the context channel on catalog-scope memories and none on user scope', function () {
    $admin = $this->loginAsAdmin();

    $suffix = random_int(10000, 99999);
    $tool = app(RememberFact::class)->register(buildPimFlexChatContext($admin));

    $tool->handle(new Request(['key' => 'pimflex_catalog_'.$suffix, 'value' => 'catalog fact', 'scope' => 'catalog']));
    $tool->handle(new Request(['key' => 'pimflex_user_'.$suffix, 'value' => 'user fact', 'scope' => 'user']));

    $catalogRow = DB::table('ai_agent_memories')->where('key', 'pimflex_catalog_'.$suffix)->first();
    $userRow = DB::table('ai_agent_memories')->where('key', 'pimflex_user_'.$suffix)->first();

    expect($catalogRow->channel)->toBe('default');
    expect($userRow->channel)->toBeNull();
});

it('recalls own-channel and legacy null-channel memories but not other-channel rows', function () {
    $admin = $this->loginAsAdmin();

    $suffix = random_int(10000, 99999);
    $needle = 'PimFlexRecallNeedle'.$suffix;

    $rows = [
        ['key' => 'pf_own_'.$suffix, 'channel' => 'default'],
        ['key' => 'pf_other_'.$suffix, 'channel' => 'pfother'.$suffix],
        ['key' => 'pf_legacy_'.$suffix, 'channel' => null],
    ];

    foreach ($rows as $row) {
        DB::table('ai_agent_memories')->insert([
            'scope'      => 'catalog',
            'key'        => $row['key'],
            'user_id'    => null,
            'channel'    => $row['channel'],
            'value'      => 'Fact '.$needle,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $result = decodePimFlexToolResult(
        app(RecallMemory::class)
            ->register(buildPimFlexChatContext($admin))
            ->handle(new Request(['search' => $needle]))
    );

    $keys = array_column($result['memories'], 'key');

    expect($keys)->toContain('pf_own_'.$suffix);
    expect($keys)->toContain('pf_legacy_'.$suffix);
    expect($keys)->not->toContain('pf_other_'.$suffix);
});

it('checks the per-user budget against the requesting user only', function () {
    $admin = $this->loginAsAdmin();
    $otherAdmin = Admin::factory()->create();

    setPimFlexCoreConfig('general.magic_ai.agentic_pim.daily_token_budget_per_user', '1000');

    insertPimFlexTokenUsage($admin->id, 500);
    insertPimFlexTokenUsage($otherAdmin->id, 5000);

    expect(exposedPimFlexBudgetCheck())->toBeNull();

    DB::table('ai_agent_token_usage')
        ->where('user_id', $admin->id)
        ->where('usage_date', now()->toDateString())
        ->update(['tokens_used' => 1500]);

    $response = exposedPimFlexBudgetCheck();

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(429);
});

it('keeps the global budget path when the per-user config is absent', function () {
    $admin = $this->loginAsAdmin();
    $otherAdmin = Admin::factory()->create();

    DB::table('core_config')->where('code', 'general.magic_ai.agentic_pim.daily_token_budget_per_user')->delete();
    setPimFlexCoreConfig('general.magic_ai.agentic_pim.daily_token_budget', '100');

    insertPimFlexTokenUsage($otherAdmin->id, 200);

    $response = exposedPimFlexBudgetCheck();

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(429);

    setPimFlexCoreConfig('general.magic_ai.agentic_pim.daily_token_budget', '0');

    expect(exposedPimFlexBudgetCheck())->toBeNull();
});

function buildPimFlexChatContext($admin): ChatContext
{
    return new ChatContext(
        message: 'PIM flexibility',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
        user: $admin,
    );
}

/**
 * Fresh family carrying only the given existing attribute codes.
 */
function createPimFlexFamily(string $code, array $attributeCodes): AttributeFamily
{
    $family = AttributeFamily::factory()->create(['code' => $code]);

    $family->familyGroups()->attach(AttributeGroup::factory()->create());

    $attributeIds = Attribute::query()->whereIn('code', $attributeCodes)->pluck('id');

    $family->attributeFamilyGroupMappings()->first()->customAttributes()->attach($attributeIds);

    return $family->fresh();
}

/**
 * Whether an attribute code appears in any bucket of a product values payload.
 */
function pimFlexValuesHaveAttribute(array $values, string $code): bool
{
    if (array_key_exists($code, $values['common'] ?? [])) {
        return true;
    }

    foreach ($values['channel_specific'] ?? [] as $attrs) {
        if (array_key_exists($code, $attrs)) {
            return true;
        }
    }

    foreach ($values['locale_specific'] ?? [] as $attrs) {
        if (array_key_exists($code, $attrs)) {
            return true;
        }
    }

    foreach ($values['channel_locale_specific'] ?? [] as $locales) {
        foreach ($locales as $attrs) {
            if (array_key_exists($code, $attrs)) {
                return true;
            }
        }
    }

    return false;
}

function setPimFlexCoreConfig(string $code, string $value): void
{
    DB::table('core_config')->where('code', $code)->delete();

    DB::table('core_config')->insert([
        'code'       => $code,
        'value'      => $value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // getConfigData memoises per request, so drop the stale entry after the
    // underlying row changes within a single test.
    $attributes = request()->attributes;

    foreach ($attributes->keys() as $key) {
        if (str_starts_with((string) $key, 'core_config_memo.')) {
            $attributes->remove($key);
        }
    }
}

function insertPimFlexTokenUsage(int $userId, int $tokens): void
{
    DB::table('ai_agent_token_usage')->insert([
        'user_id'       => $userId,
        'usage_date'    => now()->toDateString(),
        'tokens_used'   => $tokens,
        'request_count' => 1,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

function exposedPimFlexBudgetCheck(): ?JsonResponse
{
    $controller = new class(app(AgentRunner::class), app(MagicAIPlatformRepository::class)) extends ChatController
    {
        public function exposedCheckTokenBudget(): ?JsonResponse
        {
            return $this->checkTokenBudget();
        }
    };

    return $controller->exposedCheckTokenBudget();
}

function decodePimFlexToolResult(string $result): array
{
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}
