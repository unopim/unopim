<?php

namespace Webkul\AiAgent\Http\Requests;

class DestroyConversationRequest extends ConversationRequest
{
    /**
     * Owners may delete stored history even after saving it is turned off.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent.general');
    }
}
