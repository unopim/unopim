<?php

namespace Webkul\AiAgent\DTOs;

/**
 * Configuration DTO for an AI provider credential.
 * Constructed from the Credential model for type-safe passing
 * to the HTTP client and service layer.
 */
final class CredentialConfig
{
    /**
     * @param  int  $id  Credential record ID
     * @param  string  $label  Human-readable label
     * @param  string  $provider  AI provider name (openai, anthropic, etc.)
     * @param  string  $apiUrl  Base API URL
     * @param  string  $apiKey  API key / token
     * @param  string  $model  Model identifier (gpt-4, claude-3, etc.)
     * @param  array<string, mixed>  $extras  Provider-specific configuration
     */
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly string $provider,
        public readonly string $apiUrl,
        public readonly string $apiKey,
        public readonly string $model,
        public readonly array $extras = [],
    ) {}

    /**
     * Create from a Credential model (or array).
     *
     * @param  array<string, mixed>|object  $credential
     */
    public static function fromModel(object|array $credential): self
    {
        $data = is_array($credential) ? $credential : $credential->toArray();

        return new self(
            id: (int) ($data['id'] ?? 0),
            label: (string) ($data['label'] ?? ''),
            provider: (string) ($data['provider'] ?? 'openai'),
            apiUrl: (string) ($data['apiUrl'] ?? ''),
            apiKey: (string) ($data['apiKey'] ?? ''),
            model: (string) ($data['model'] ?? ''),
            extras: (array) ($data['extras'] ?? []),
        );
    }
}
