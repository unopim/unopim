<?php

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools\ManageUsers;
use Webkul\AiAgent\Chat\Tools\SearchProducts;
use Webkul\Core\Platform\Module;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\MagicAI\Models\MagicAIPlatform;

uses(InteractsWithPlatformModules::class);

abstract class PlatformModuleStubTool implements PimTool
{
    abstract protected function toolName(): string;

    public function register(ChatContext $context): Tool
    {
        $name = $this->toolName();

        return new class($name) implements Tool
        {
            public function __construct(private string $toolName) {}

            public function name(): string
            {
                return $this->toolName;
            }

            public function description(): Stringable|string
            {
                return 'Platform module stub tool.';
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

class PlatformModuleUntaggedTool extends PlatformModuleStubTool
{
    protected function toolName(): string
    {
        return 'untagged_tool';
    }
}

class PlatformModulePimReadTool extends PlatformModuleStubTool
{
    protected function toolName(): string
    {
        return 'pim_read_tool';
    }
}

class PlatformModulePimWriteTool extends PlatformModuleStubTool
{
    protected function toolName(): string
    {
        return 'pim_write_tool';
    }
}

function platformModuleRegistry(): ToolRegistry
{
    return (new ToolRegistry)
        ->register(new PlatformModuleUntaggedTool, ['name' => 'untagged_tool', 'group' => 'general', 'guidance' => 'Always here.'])
        ->register(new PlatformModulePimReadTool, ['name' => 'pim_read_tool', 'group' => 'catalog', 'module' => 'pim', 'guidance' => 'Catalog only.'])
        ->register(new PlatformModulePimWriteTool, ['name' => 'pim_write_tool', 'group' => 'catalog', 'write' => true, 'module' => Module::Pim]);
}

function platformModuleChatContext(): ChatContext
{
    return new ChatContext(
        message: 'hello',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: (new MagicAIPlatform)->forceFill(['provider' => 'openai']),
        user: null,
    );
}

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);
});

it('stores an enum module tag as its string value', function () {
    expect(platformModuleRegistry()->metadata('pim_write_tool')['module'])->toBe('pim')
        ->and(platformModuleRegistry()->metadata('untagged_tool')['module'])->toBeNull();
});

it('offers pim tools and untagged tools while pim is on', function () {
    $this->setPlatformModules('pim');

    $registry = platformModuleRegistry();

    expect($registry->namesByGroup())->toBe(['general' => ['untagged_tool'], 'catalog' => ['pim_read_tool', 'pim_write_tool']])
        ->and($registry->readToolNames())->toBe(['untagged_tool', 'pim_read_tool'])
        ->and($registry->writeToolNames())->toBe(['pim_write_tool'])
        ->and(array_map(fn (Tool $tool): string => $tool->name(), $registry->build(platformModuleChatContext())))
        ->toBe(['untagged_tool', 'pim_read_tool', 'pim_write_tool']);
});

it('hides pim tools but keeps untagged tools while pim is off', function () {
    $this->setPlatformModules('records');

    $registry = platformModuleRegistry();

    expect($registry->namesByGroup())->toBe(['general' => ['untagged_tool']])
        ->and($registry->readToolNames())->toBe(['untagged_tool'])
        ->and($registry->writeToolNames())->toBe([])
        ->and($registry->guidanceNotes())->toBe(['untagged_tool' => 'Always here.'])
        ->and(array_map(fn (Tool $tool): string => $tool->name(), $registry->build(platformModuleChatContext())))
        ->toBe(['untagged_tool'])
        ->and($registry->has('pim_read_tool'))->toBeTrue();
});

it('tags the core catalog tools with pim and leaves user management untagged', function () {
    expect(config('ai-agent.tools.'.SearchProducts::class.'.module'))->toBe('pim')
        ->and(config('ai-agent.tools.'.ManageUsers::class))->toBeArray()
        ->and(config('ai-agent.tools.'.ManageUsers::class))->not->toHaveKey('module');
});

it('drops the core catalog tools from the registry while pim is off', function () {
    $this->setPlatformModules('records');

    $registry = app(ToolRegistry::class);

    expect($registry->readToolNames())->not->toContain('search_products')
        ->and(array_merge(...array_values($registry->namesByGroup())))->toContain('manage_users');
});
