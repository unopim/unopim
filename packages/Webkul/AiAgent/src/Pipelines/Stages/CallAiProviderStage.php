<?php

namespace Webkul\AiAgent\Pipelines\Stages;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Repositories\CredentialRepository;

/**
 * Calls the AI provider API via the cURL client and attaches
 * the response to the payload metadata.
 */
class CallAiProviderStage implements PipelineStageContract
{
    public function __construct(
        protected AiApiClient $apiClient,
        protected CredentialRepository $credentialRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $credential = $this->credentialRepository->findOrFail($payload->credentialId);
        $config = CredentialConfig::fromModel($credential);

        $this->apiClient->configure($config);

        $response = $this->apiClient->chat(
            messages: $payload->messages,
            maxTokens: $payload->metadata['maxTokens'] ?? 4096,
            temperature: $payload->metadata['temperature'] ?? 0.7,
        );

        $enriched = $payload->withMetadata([
            'aiResponse'  => $response['content'] ?? '',
            'tokensUsed'  => $response['tokensUsed'] ?? 0,
            'rawResponse' => $response,
        ]);

        return $next($enriched);
    }
}
