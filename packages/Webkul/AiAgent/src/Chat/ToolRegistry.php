<?php

namespace Webkul\AiAgent\Chat;

use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Webkul\AiAgent\Chat\Contracts\AuthorizesContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

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

    /** @var array<class-string<PimTool>, array{name: string, group: string, write: bool, permission: ?string, guidance: ?string}> */
    protected array $metadata = [];

    /**
     * Register a PIM tool with optional metadata (name, group, write,
     * permission, guidance). Registering the same class again replaces the
     * previous instance and metadata, so packages can override core tools.
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
     * @return array{name: string, group: string, write: bool, permission: ?string, guidance: ?string}|null
     */
    public function metadata(string $classOrName): ?array
    {
        $class = $this->resolveClass($classOrName);

        return $class ? $this->metadata[$class] : null;
    }

    /**
     * Tool names grouped by their metadata group, in registration order.
     *
     * @return array<string, string[]>
     */
    public function namesByGroup(): array
    {
        $groups = [];

        foreach ($this->metadata as $meta) {
            $groups[$meta['group']][] = $meta['name'];
        }

        return $groups;
    }

    /**
     * Names of all registered tools that can mutate data (write=true).
     *
     * @return string[]
     */
    public function writeToolNames(): array
    {
        return array_values(array_map(
            fn (array $meta) => $meta['name'],
            array_filter($this->metadata, fn (array $meta) => $meta['write']),
        ));
    }

    /**
     * Names of all registered read-only tools (write=false).
     *
     * @return string[]
     */
    public function readToolNames(): array
    {
        return array_values(array_map(
            fn (array $meta) => $meta['name'],
            array_filter($this->metadata, fn (array $meta) => ! $meta['write']),
        ));
    }

    /**
     * Per-tool prompt guidance keyed by tool name (tools without guidance omitted).
     *
     * @return array<string, string>
     */
    public function guidanceNotes(): array
    {
        $notes = [];

        foreach ($this->metadata as $meta) {
            if ($meta['guidance'] !== null && $meta['guidance'] !== '') {
                $notes[$meta['name']] = $meta['guidance'];
            }
        }

        return $notes;
    }

    /**
     * Build the laravel/ai Tool[] array for a given chat context.
     *
     * Tools whose `permission` metadata the user lacks, and tools whose
     * authorize() hook rejects the context, are excluded up front so the
     * LLM never sees them (the per-tool in-handle checks remain as defense
     * in depth).
     *
     * @return Tool[]
     */
    public function build(ChatContext $context): array
    {
        $built = [];

        foreach ($this->tools as $class => $tool) {
            $permission = $this->metadata[$class]['permission'];

            if ($permission !== null && ! $context->hasPermission($permission)) {
                continue;
            }

            if ($tool instanceof AuthorizesContext && ! $tool->authorize($context)) {
                continue;
            }

            $built[] = $tool->register($context);
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
