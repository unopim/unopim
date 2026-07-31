<?php

namespace Webkul\AiAgent\Services;

use Webkul\AiAgent\Contracts\PromptBuilderContract;
use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Default prompt builder: assembles the message array
 * from system prompt, context, and user instruction.
 *
 * Message ordering is deliberate for provider prompt caching (issue #421):
 * static content (system instructions) is emitted FIRST in a deterministic
 * byte order, dynamic content (context data, user instruction) LAST, so
 * OpenAI automatic prefix caching and Anthropic cache_control breakpoints
 * get a stable, reusable prefix.
 */
class PromptBuilder implements PromptBuilderContract
{
    /**
     * {@inheritdoc}
     */
    public function build(AgentPayload $payload): array
    {
        $messages = [];

        // 1. Static prefix: system message from agent config.
        $systemPrompt = $payload->metadata['systemPrompt'] ?? null;

        if ($systemPrompt) {
            $messages[] = [
                'role'    => 'system',
                'content' => $systemPrompt,
            ];
        }

        // 2. Dynamic context (product data, filters) as a system-level message.
        //    Keys are sorted recursively so identical context always serializes
        //    to identical bytes.
        if ($payload->context !== []) {
            $contextJson = json_encode(
                $this->sortKeysRecursively($payload->context),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            );

            $messages[] = [
                'role'    => 'system',
                'content' => "Context data:\n".$contextJson,
            ];
        }

        // 3. Dynamic user instruction always comes last.
        $messages[] = [
            'role'    => 'user',
            'content' => $payload->instruction,
        ];

        return $messages;
    }

    /**
     * Recursively sort associative keys for deterministic serialization.
     * List (sequential) arrays keep their original order.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    protected function sortKeysRecursively(array $data): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->sortKeysRecursively($value);
            }
        }

        unset($value);

        if (! array_is_list($data)) {
            ksort($data);
        }

        return $data;
    }
}
