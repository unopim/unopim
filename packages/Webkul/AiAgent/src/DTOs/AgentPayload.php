<?php

namespace Webkul\AiAgent\DTOs;

/**
 * Immutable Data Transfer Object carrying the agent execution payload
 * through the pipeline. Each pipeline stage reads from and enriches
 * this DTO without modifying the original.
 */
final class AgentPayload
{
    /**
     * @param  int  $agentId  The agent configuration ID
     * @param  int  $credentialId  The AI provider credential ID
     * @param  string  $instruction  User-provided instruction/prompt
     * @param  array<string, mixed>  $context  Contextual data (product IDs, filters, etc.)
     * @param  array<int, array{role: string, content: string}>  $messages  Accumulated messages for the AI API
     * @param  array<string, mixed>  $metadata  Pipeline-enriched metadata
     */
    public function __construct(
        public readonly int $agentId,
        public readonly int $credentialId,
        public readonly string $instruction,
        public readonly array $context = [],
        public readonly array $messages = [],
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a new instance with additional messages appended.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function withMessages(array $messages): self
    {
        return new self(
            agentId: $this->agentId,
            credentialId: $this->credentialId,
            instruction: $this->instruction,
            context: $this->context,
            messages: array_merge($this->messages, $messages),
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new instance with merged metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            agentId: $this->agentId,
            credentialId: $this->credentialId,
            instruction: $this->instruction,
            context: $this->context,
            messages: $this->messages,
            metadata: array_merge($this->metadata, $metadata),
        );
    }

    /**
     * Create from an array (e.g. deserialized job payload).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            agentId: (int) ($data['agentId'] ?? 0),
            credentialId: (int) ($data['credentialId'] ?? 0),
            instruction: (string) ($data['instruction'] ?? ''),
            context: (array) ($data['context'] ?? []),
            messages: (array) ($data['messages'] ?? []),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    /**
     * Serialize to array for queue jobs.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agentId'      => $this->agentId,
            'credentialId' => $this->credentialId,
            'instruction'  => $this->instruction,
            'context'      => $this->context,
            'messages'     => $this->messages,
            'metadata'     => $this->metadata,
        ];
    }
}
