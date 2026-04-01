<?php

namespace Webkul\AiAgent\Chat\Contracts;

use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;

/**
 * Contract for PIM tools that can be registered with the agent.
 *
 * Each tool is a standalone, self-describing unit. The agent (LLM)
 * autonomously decides which tools to call based on the user's intent.
 * New PIM capabilities are added by implementing this interface and
 * registering the class in the ToolRegistry — zero routing changes.
 */
interface PimTool
{
    /**
     * Return a configured Prism Tool instance.
     *
     * The ChatContext provides access to product context, locale, channel,
     * uploaded files, and other request-scoped data that tools may need.
     */
    public function register(ChatContext $context): Tool;
}
