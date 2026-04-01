<?php

namespace Webkul\AiAgent\Pipelines\Stages\Image;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\ApiException;
use Webkul\AiAgent\Exceptions\PipelineException;
use Webkul\AiAgent\Services\VisionService;

/**
 * Stage 2 — Delegates to VisionService to submit the normalised image to
 * the AI vision model and stores the structured output for downstream stages.
 *
 * Reads from metadata (set by ImageUploadStep):
 *   imageContent   — base64 data URI or URL
 *
 * Reads from context (optional overrides):
 *   visionPrompt       — custom system-level instruction
 *   detectionLocale    — locale code for the AI response (default: en)
 *   visionMaxAttempts  — retry limit (default: 3)
 *   visionTemperature  — sampling temperature (default: 0.2)
 *
 * Writes to metadata:
 *   imageContext       ImageProductContext array  (detectedProduct, attributes, category, rawAiResponse updated)
 *   visionDetections   array   Mapped PIM attribute values from VisionService
 *   visionRawResponse  string  Raw AI text response
 *   visionModel        string  Model identifier used for this call
 */
class VisionDetectionStep implements PipelineStageContract
{
    public function __construct(
        protected VisionService $visionService,
    ) {}

    /**
     * {@inheritdoc}
     *
     * @throws PipelineException
     * @throws ApiException
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $imageContent = $payload->metadata['imageContent'] ?? null;

        if (empty($imageContent)) {
            throw new PipelineException(
                'VisionDetectionStep: imageContent is missing — ImageUploadStep must run first.',
                self::class,
            );
        }

        $options = [
            'systemPrompt' => $payload->context['visionPrompt'] ?? null,
            'locale'       => $payload->context['detectionLocale'] ?? 'en',
            'maxAttempts'  => $payload->context['visionMaxAttempts'] ?? 3,
            'temperature'  => $payload->context['visionTemperature'] ?? 0.2,
            'maxTokens'    => 2048,
        ];

        // Remove null systemPrompt so VisionService uses its DEFAULT_SYSTEM_PROMPT
        if ($options['systemPrompt'] === null) {
            unset($options['systemPrompt']);
        }

        $visionCtx = $this->visionService->analyze(
            imageContent: $imageContent,
            credentialId: $payload->credentialId,
            options: $options,
        );

        // Merge vision output into the flowing ImageProductContext DTO
        $ctx = ImageProductContext::fromArray($payload->metadata['imageContext'] ?? []);

        if (! empty($visionCtx->detectedProduct)) {
            $ctx = $ctx->withDetectedProduct($visionCtx->detectedProduct);
        }

        if (! empty($visionCtx->rawAiResponse)) {
            $ctx = $ctx->withRawAiResponse($visionCtx->rawAiResponse);
        }

        if (! empty($visionCtx->category)) {
            $ctx = $ctx->withCategory($visionCtx->category);
        }

        if (! empty($visionCtx->attributes)) {
            $ctx = $ctx->withAttributes($visionCtx->attributes);
        }

        return $next($payload->withMetadata([
            'imageContext'      => $ctx->toArray(),
            'visionDetections'  => $visionCtx->attributes,
            'visionRawResponse' => $visionCtx->rawAiResponse ?? '',
        ]));
    }
}
