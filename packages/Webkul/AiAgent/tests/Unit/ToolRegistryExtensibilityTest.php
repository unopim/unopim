<?php

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\AuthorizesContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools\SearchProducts;
use Webkul\AiAgent\Events\AgentSystemPromptBuilding;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ToolRegistryExtensibilityStubTool implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class implements Tool
        {
            public function name(): string
            {
                return 'extensibility_stub';
            }

            public function description(): Stringable|string
            {
                return 'Extensibility test stub tool.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [];
            }

            public function handle(Request $request): Stringable|string
            {
                return '{}';
            }
        };
    }
}

class ToolRegistryExtensibilityDeniedTool extends ToolRegistryExtensibilityStubTool implements AuthorizesContext
{
    public function authorize(ChatContext $context): bool
    {
        return false;
    }
}

class ToolRegistryExtensibilityAllowedTool extends ToolRegistryExtensibilityStubTool implements AuthorizesContext
{
    public function authorize(ChatContext $context): bool
    {
        return true;
    }
}

function extensibilityChatContext(?Admin $user = null): ChatContext
{
    return new ChatContext(
        message: 'hello catalog',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: (new MagicAIPlatform)->forceFill(['provider' => 'openai']),
        user: $user,
    );
}

function extensibilityFullAccessAdmin(): Admin
{
    $admin = new Admin;
    $admin->setRelation('role', (new Role)->forceFill(['permission_type' => 'all']));

    return $admin;
}

function extensibilityBuildSystemPrompt(ChatContext $context): string
{
    $method = new ReflectionMethod(AgentRunner::class, 'buildSystemPrompt');

    return $method->invoke(app(AgentRunner::class), $context);
}

function extensibilityMockCoreConfig(array $overrides): void
{
    $core = Mockery::mock(app('core'))->makePartial();

    foreach ($overrides as $key => $value) {
        $core->shouldReceive('getConfigData')->with($key)->andReturn($value);
    }

    app()->instance('core', $core);
}

function extensibilityBuiltToolNames(array $tools): array
{
    return array_map(fn (Tool $tool) => $tool->name(), $tools);
}

it('builds the registry from the ai-agent.tools config map', function () {
    $registry = app(ToolRegistry::class);

    expect($registry->count())->toBe(count(config('ai-agent.tools')));
    expect($registry->has(SearchProducts::class))->toBeTrue();
    expect($registry->has('search_products'))->toBeTrue();
    expect($registry->metadata('search_products'))->toMatchArray([
        'name'       => 'search_products',
        'group'      => 'catalog',
        'write'      => false,
        'permission' => 'catalog.products',
    ]);
});

it('skips config entries flagged enabled=false', function () {
    config()->set('ai-agent.tools.'.SearchProducts::class.'.enabled', false);
    app()->forgetInstance(ToolRegistry::class);

    $registry = app(ToolRegistry::class);

    expect($registry->has('search_products'))->toBeFalse();
    expect($registry->count())->toBe(count(config('ai-agent.tools')) - 1);
});

it('removes a tool from build() when disabled by name or class', function () {
    $registry = app(ToolRegistry::class);
    $registry->disable('search_products');
    $registry->disable(\Webkul\AiAgent\Chat\Tools\GetProductDetails::class);

    expect($registry->has('search_products'))->toBeFalse();
    expect($registry->has('get_product_details'))->toBeFalse();

    $names = extensibilityBuiltToolNames($registry->build(extensibilityChatContext(extensibilityFullAccessAdmin())));

    expect($names)->not->toContain('search_products');
    expect($names)->not->toContain('get_product_details');
    expect($names)->toContain('create_product');
});

it('replaces instance and metadata when the same class is registered again', function () {
    $registry = new ToolRegistry;
    $tool = app(SearchProducts::class);

    $registry->register($tool, ['name' => 'search_products', 'group' => 'catalog', 'write' => false]);
    $registry->register($tool, ['name' => 'search_products', 'group' => 'catalog', 'write' => true, 'guidance' => 'Prefer SKU search.']);

    expect($registry->count())->toBe(1);
    expect($registry->writeToolNames())->toBe(['search_products']);
    expect($registry->guidanceNotes())->toBe(['search_products' => 'Prefer SKU search.']);
});

it('defaults metadata from the class name when registered without any', function () {
    $registry = new ToolRegistry;
    $registry->register(new ToolRegistryExtensibilityStubTool);

    expect($registry->has('tool_registry_extensibility_stub_tool'))->toBeTrue();
    expect($registry->metadata('tool_registry_extensibility_stub_tool'))->toMatchArray([
        'group'      => 'general',
        'write'      => false,
        'permission' => null,
    ]);
});

it('offers only permissionless tools to a user who fails every ACL check', function () {
    $names = extensibilityBuiltToolNames(
        app(ToolRegistry::class)->build(extensibilityChatContext(user: null))
    );

    sort($names);

    expect($names)->toBe(['plan_tasks', 'rate_content', 'recall_memory', 'remember_fact']);
});

it('offers every registered tool to a full-access user', function () {
    $names = extensibilityBuiltToolNames(
        app(ToolRegistry::class)->build(extensibilityChatContext(extensibilityFullAccessAdmin()))
    );

    expect($names)->toHaveCount(count(config('ai-agent.tools')));
    expect($names)->toContain('search_products');
    expect($names)->toContain('manage_users');
});

it('honours the AuthorizesContext pre-filter during build()', function () {
    $registry = new ToolRegistry;
    $registry->register(new ToolRegistryExtensibilityDeniedTool, ['name' => 'denied_tool']);
    $registry->register(new ToolRegistryExtensibilityAllowedTool, ['name' => 'allowed_tool']);

    $built = $registry->build(extensibilityChatContext(user: null));

    expect($built)->toHaveCount(1);
    expect($built[0]->name())->toBe('extensibility_stub');
});

it('renders the tool groups section of the system prompt from registry metadata', function () {
    app(ToolRegistry::class)->register(
        new ToolRegistryExtensibilityStubTool,
        ['name' => 'plugin_stub_tool', 'group' => 'plugin', 'write' => false, 'guidance' => 'Use only for extensibility tests.'],
    );

    $prompt = extensibilityBuildSystemPrompt(extensibilityChatContext(extensibilityFullAccessAdmin()));

    expect($prompt)->toContain('Tool Groups (use the right tools for each task):');
    expect($prompt)->toContain('- CATALOG: search_products, get_product_details,');
    expect($prompt)->toContain('- INTELLIGENCE: catalog_summary,');
    expect($prompt)->toContain('- PLUGIN: plugin_stub_tool');
    expect($prompt)->toContain('Tool Notes:');
    expect($prompt)->toContain('- plugin_stub_tool: Use only for extensibility tests.');
});

it('lists registered write tools, including plugin ones, in suggest mode', function () {
    extensibilityMockCoreConfig([
        'general.magic_ai.agentic_pim.approval_mode' => 'suggest',
    ]);

    app(ToolRegistry::class)->register(
        new ToolRegistryExtensibilityStubTool,
        ['name' => 'plugin_write_tool', 'group' => 'plugin', 'write' => true],
    );

    $prompt = extensibilityBuildSystemPrompt(extensibilityChatContext(extensibilityFullAccessAdmin()));

    expect($prompt)->toContain('APPROVAL MODE — SUGGEST ONLY:');
    expect($prompt)->toMatch('/Do NOT call any write tools \([^)]*plugin_write_tool[^)]*\)/');
    expect($prompt)->toMatch('/Do NOT call any write tools \([^)]*create_product[^)]*\)/');
    expect($prompt)->toMatch('/Only use read-only tools: [^\n]*search_products/');
});

it('lists registered write tools in review mode wording', function () {
    extensibilityMockCoreConfig([
        'general.magic_ai.agentic_pim.approval_mode' => 'review',
    ]);

    $prompt = extensibilityBuildSystemPrompt(extensibilityChatContext(extensibilityFullAccessAdmin()));

    expect($prompt)->toContain('APPROVAL MODE — REVIEW:');
    expect($prompt)->toMatch('/For ALL write operations \([^)]*delete_products[^)]*\)/');
});

it('lets listeners mutate the prompt via AgentSystemPromptBuilding', function () {
    Event::listen(AgentSystemPromptBuilding::class, function (AgentSystemPromptBuilding $event) {
        $event->prompt .= "\nEXTENSIBILITY_EVENT_MARKER";
    });

    $prompt = extensibilityBuildSystemPrompt(extensibilityChatContext(extensibilityFullAccessAdmin()));

    expect($prompt)->toEndWith('EXTENSIBILITY_EVENT_MARKER');
});

it('appends admin-configured custom instructions under INSTALL RULES', function () {
    extensibilityMockCoreConfig([
        'general.magic_ai.agentic_pim.custom_instructions' => 'Always answer in British English.',
    ]);

    $prompt = extensibilityBuildSystemPrompt(extensibilityChatContext(extensibilityFullAccessAdmin()));

    expect($prompt)->toContain("INSTALL RULES:\nAlways answer in British English.");
});

it('omits the INSTALL RULES section when no custom instructions are configured', function () {
    extensibilityMockCoreConfig([
        'general.magic_ai.agentic_pim.custom_instructions' => '   ',
    ]);

    $prompt = extensibilityBuildSystemPrompt(extensibilityChatContext(extensibilityFullAccessAdmin()));

    expect($prompt)->not->toContain('INSTALL RULES:');
});
