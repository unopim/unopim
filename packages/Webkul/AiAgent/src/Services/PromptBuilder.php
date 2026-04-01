<?php

namespace Webkul\AiAgent\Services;

use Webkul\AiAgent\Contracts\PromptBuilderContract;
use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Default prompt builder: assembles the message array
 * from system prompt, context, and user instruction.
 */
class PromptBuilder implements PromptBuilderContract
{
    /**
     * {@inheritdoc}
     */
    public function build(AgentPayload $payload): array
    {
        $messages = [];

        // System message from agent config
        $systemPrompt = $payload->metadata['systemPrompt'] ?? null;

        if ($systemPrompt) {
            $messages[] = [
                'role'    => 'system',
                'content' => $systemPrompt,
            ];
        }

        // Inject context as a system-level message if present
        if (! empty($payload->context)) {
            $contextJson = json_encode($payload->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $messages[] = [
                'role'    => 'system',
                'content' => "Context data:\n".$contextJson,
            ];
        }

        // User instruction
        $messages[] = [
            'role'    => 'user',
            'content' => $payload->instruction,
        ];

        return $messages;
    }
}
