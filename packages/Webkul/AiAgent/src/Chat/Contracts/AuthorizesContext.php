<?php

namespace Webkul\AiAgent\Chat\Contracts;

use Webkul\AiAgent\Chat\ChatContext;

/**
 * Optional contract for PIM tools that gate their availability per request.
 *
 * When a registered tool implements this interface and authorize() returns
 * false for the current chat context, the tool is excluded from the set sent
 * to the LLM entirely — the model never sees it. Use this for checks beyond
 * a single ACL permission string (feature flags, channel scoping, etc.).
 */
interface AuthorizesContext
{
    /**
     * Whether this tool may be offered to the LLM for the given context.
     */
    public function authorize(ChatContext $context): bool;
}
