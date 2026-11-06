<?php

namespace Webkul\AiAgent\Chat;

use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\ToolNameResolver;
use UnexpectedValueException;
use Webkul\AiAgent\Chat\Contracts\AuthorizesContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Platform\Module;
use Webkul\Core\Platform\ModuleGate;

/**
 * Collects PIM tool classes and builds the laravel/ai Tool[] array for each request.
 *
 * Registered as a singleton in the service provider and populated from the
 * `ai-agent.tools` config map. Third-party packages extend the agent either
 * by merging entries into that config or by resolving this class and calling
 * register() — no routing code changes required.
 */
class ToolRegistry
{
    /** @var array<class-string<PimTool>, PimTool> */
    protected array $tools = [];

    /** @var list<callable(Tool, ChatContext, array{name: string, group: string, write: bool, permission: ?string, guidance: ?string, module: string}): Tool> */
    protected array $decorators = [];

    /** @var array<class-string<PimTool>, array{name: string, group: string, write: bool, permission: ?string, guidance: ?string, module: string}> */
    protected array $metadata = [];

    /**
     * Wrap every built tool for each request, in registration order.
     *
     * A decorator must delegate to the tool it receives, never replace it: the
     * wrapped tool's handle() carries its in-handle permission checks, so the
     * returned tool must keep the same name or build() throws.
     *
     * @param  callable(Tool, ChatContext, array{name: string, group: string, write: bool, permission: ?string, guidance: ?string, module: string}): Tool  $decorator
     */
    public function decorate(callable $decorator): void
    {
        $this->decorators[] = $decorator;
    }

    /**
     * Register a PIM tool with optional metadata (name, group, write,
     * permission, guidance, module). Registering the same class again replaces the
     * previous instance and metadata, so packages can override core tools.
     *
     * The `module` defaults to `pim`, so a tool is only offered while its platform
     * module is switched on.
     */
    public function register(PimTool $tool, array $metadata = []): static
    {
        $class = $tool::class;

        $this->tools[$class] = $tool;

        $this->metadata[$class] = [
            'name'       => (string) ($metadata['name'] ?? Str::snake(class_basename($class))),
            'group'      => (string) ($metadata['group'] ?? 'general'),
            'write'      => (bool) ($metadata['write'] ?? false),
            'permission' => isset($metadata['permission']) ? (string) $metadata['permission'] : null,
            'guidance'   => isset($metadata['guidance']) ? (string) $metadata['guidance'] : null,
            'module'     => $this->moduleValue($metadata['module'] ?? null),
        ];

        return $this;
    }

    /**
     * Remove a tool by its class name or registered tool name.
     */
    public function disable(string $classOrName): void
    {
        if ($class = $this->resolveClass($classOrName)) {
            unset($this->tools[$class], $this->metadata[$class]);
        }
    }

    /**
     * Whether a tool is registered, by class name or tool name.
     */
    public function has(string $classOrName): bool
    {
        return $this->resolveClass($classOrName) !== null;
    }

    /**
     * All registered tools keyed by class name.
     *
     * @return array<class-string<PimTool>, PimTool>
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Metadata for a single tool, or null when not registered.
     *
     * @return array{name: string, group: string, write: bool, permission: ?string, guidance: ?string, module: string}|null
     */
    public function metadata(string $classOrName): ?array
    {
        $class = $this->resolveClass($classOrName);

        return $class ? $this->metadata[$class] : null;
    }

    /**
     * Tool names grouped by their metadata group, in registration order, for tools
     * whose platform module is switched on.
     *
     * @return array<string, string[]>
     */
    public function namesByGroup(): array
    {
        $groups = [];

        foreach ($this->activeMetadata() as $meta) {
            $groups[$meta['group']][] = $meta['name'];
        }

        return $groups;
    }

    /**
     * Names of the active tools that can mutate data (write=true).
     *
     * @return string[]
     */
    public function writeToolNames(): array
    {
        return array_values(array_map(
            fn (array $meta): string => $meta['name'],
            array_filter($this->activeMetadata(), fn (array $meta): bool => $meta['write']),
        ));
    }

    /**
     * Names of the active read-only tools (write=false).
     *
     * @return string[]
     */
    public function readToolNames(): array
    {
        return array_values(array_map(
            fn (array $meta): string => $meta['name'],
            array_filter($this->activeMetadata(), fn (array $meta): bool => ! $meta['write']),
        ));
    }

    /**
     * Per-tool prompt guidance of the active tools, keyed by tool name (tools without guidance omitted).
     *
     * @return array<string, string>
     */
    public function guidanceNotes(): array
    {
        $notes = [];

        foreach ($this->activeMetadata() as $meta) {
            if ($meta['guidance'] !== null && $meta['guidance'] !== '') {
                $notes[$meta['name']] = $meta['guidance'];
            }
        }

        return $notes;
    }

    /**
     * Build the laravel/ai Tool[] array for a given chat context.
     *
     * Tools of a switched-off platform module, tools whose `permission` metadata
     * the user lacks, and tools whose authorize() hook rejects the context, are
     * excluded up front so the LLM never sees them (the per-tool in-handle checks
     * remain as defense in depth).
     *
     * @return Tool[]
     */
    public function build(ChatContext $context): array
    {
        $built = [];

        $active = $this->activeMetadata();

        foreach ($this->tools as $class => $tool) {
            if (! isset($active[$class])) {
                continue;
            }

            $permission = $this->metadata[$class]['permission'];

            if ($permission !== null && ! $context->hasPermission($permission)) {
                continue;
            }

            if ($tool instanceof AuthorizesContext && ! $tool->authorize($context)) {
                continue;
            }

            $registered = $tool->register($context);

            foreach ($this->decorators as $decorator) {
                $decorated = $decorator($registered, $context, $this->metadata[$class]);

                if (! $decorated instanceof Tool) {
                    throw new UnexpectedValueException(sprintf('Decorator for tool "%s" must return %s; received %s.', $this->metadata[$class]['name'], Tool::class, get_debug_type($decorated)));
                }

                if ($decorated !== $registered && ToolNameResolver::resolve($decorated) !== ToolNameResolver::resolve($registered)) {
                    throw new UnexpectedValueException(sprintf('Decorator for tool "%s" must preserve the tool name; received "%s".', $this->metadata[$class]['name'], ToolNameResolver::resolve($decorated)));
                }

                $registered = $decorated;
            }

            $built[] = $registered;
        }

        return $built;
    }

    /**
     * Get the count of registered tools.
     */
    public function count(): int
    {
        return count($this->tools);
    }

    /**
     * Metadata of the tools whose platform module is switched on; an unknown module never is.
     *
     * @return array<class-string<PimTool>, array{name: string, group: string, write: bool, permission: ?string, guidance: ?string, module: string}>
     */
    protected function activeMetadata(): array
    {
        $gate = app(ModuleGate::class);

        return array_filter($this->metadata, fn (array $meta): bool => $gate->enabled($meta['module']));
    }

    /**
     * Normalise a `module` metadata value to its string form, defaulting to `pim`.
     */
    protected function moduleValue(mixed $module): string
    {
        if ($module instanceof Module) {
            return $module->value;
        }

        return is_string($module) && $module !== '' ? $module : Module::Pim->value;
    }

    /**
     * Resolve a class name or tool name to the registered class key.
     *
     * @return class-string<PimTool>|null
     */
    protected function resolveClass(string $classOrName): ?string
    {
        if (isset($this->tools[$classOrName])) {
            return $classOrName;
        }

        foreach ($this->metadata as $class => $meta) {
            if ($meta['name'] === $classOrName) {
                return $class;
            }
        }

        return null;
    }
}
