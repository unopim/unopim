<?php

declare(strict_types=1);

namespace Webkul\AiAgent\Chat;

use Laravel\Ai\Contracts\Tool;
use Webkul\AiAgent\Chat\Contracts\PimTool;

/**
 * Collects PIM tool classes and builds the laravel/ai Tool[] array for each request.
 *
 * Registered as a singleton in the service provider. Third-party packages
 * can resolve this class and call register() to add their own tools —
 * no routing code changes required.
 */
class ToolRegistry
{
    /** @var PimTool[] */
    protected array $tools = [];

    /**
     * Register a PIM tool.
     */
    public function register(PimTool $tool): static
    {
        $this->tools[] = $tool;

        return $this;
    }

    /**
     * Build the laravel/ai Tool[] array for a given chat context.
     *
     * @return Tool[]
     */
    public function build(ChatContext $context): array
    {
        return array_map(
            fn (PimTool $tool) => $tool->register($context),
            $this->tools,
        );
    }

    /**
     * Get the count of registered tools.
     */
    public function count(): int
    {
        return count($this->tools);
    }
}
