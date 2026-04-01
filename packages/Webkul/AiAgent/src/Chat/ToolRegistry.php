<?php

namespace Webkul\AiAgent\Chat;

use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\Contracts\PimTool;

/**
 * Collects PIM tool classes and builds the Prism Tool[] array for each request.
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
     * Build the Prism Tool[] array for a given chat context.
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
