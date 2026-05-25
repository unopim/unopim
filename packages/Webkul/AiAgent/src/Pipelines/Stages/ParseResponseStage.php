<?php

namespace Webkul\AiAgent\Pipelines\Stages;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Parses the raw AI response and extracts structured data.
 * Handles JSON extraction from markdown code blocks if present.
 */
class ParseResponseStage implements PipelineStageContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $rawContent = $payload->metadata['aiResponse'] ?? '';

        $parsed = $this->extractJson($rawContent);

        $enriched = $payload->withMetadata([
            'parsedData'  => $parsed,
            'parseStatus' => $parsed !== null ? 'structured' : 'plain_text',
        ]);

        return $next($enriched);
    }

    /**
     * Attempt to extract JSON from response content.
     *
     * @return array<mixed>|null
     */
    protected function extractJson(string $content): ?array
    {
        // Try direct JSON decode first
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try extracting from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
