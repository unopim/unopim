<?php

namespace Webkul\AiAgent\Pipelines\Stages\Image;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\PipelineException;

/**
 * Stage 6 — Persists a product draft in the Unopim PIM using all
 * enriched attributes and confidence metadata collected by the earlier
 * pipeline stages.
 *
 * The draft is given status "draft" by default. Attributes with a
 * confidence score below the review threshold (or that appear in
 * lowConfidenceFields) are stored but flagged for manual review.
 *
 * Reads from metadata:
 *   enrichedAttributes   — final PIM attribute map
 *   confidenceScores     — per-key confidence values
 *   overallConfidence    — mean confidence
 *   requiresReview       — whether manual review is needed
 *   lowConfidenceFields  — attribute keys flagged for review
 *   imageSource          — original image source URL/path
 *
 * Reads from context (optional):
 *   draftStatus     string  'draft' (default) | 'pending_review'
 *   sku             string  Explicit SKU; auto-generated if omitted
 *   channelCode     string  Unopim channel code (default: 'default')
 *   localeCode      string  Unopim locale code  (default: 'en_US')
 *
 * Writes to metadata:
 *   productDraftId   int|string  ID of the created draft record
 *   productDraft     array       Full draft data as persisted
 *   draftStatus      string      Status stored on the record
 */
class CreateProductDraftStep implements PipelineStageContract
{
    /**
     * @throws PipelineException
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $enriched = $payload->metadata['enrichedAttributes'] ?? null;

        if (! is_array($enriched)) {
            throw new PipelineException(
                'CreateProductDraftStep: enrichedAttributes is missing — EnrichmentStep must run first.',
                self::class,
            );
        }

        $draft = $this->buildDraft($payload, $enriched);

        $draftId = $this->persist($draft);

        $ctx = ImageProductContext::fromArray($payload->metadata['imageContext'] ?? [])
            ->withProductId($draftId);

        return $next($payload->withMetadata([
            'imageContext'   => $ctx->toArray(),
            'productDraftId' => $draftId,
            'productDraft'   => $draft,
            'draftStatus'    => $draft['status'],
        ]));
    }

    /**
     * Build the product draft array from pipeline metadata.
     *
     * @param  array<string, mixed>  $enriched
     * @return array<string, mixed>
     */
    protected function buildDraft(AgentPayload $payload, array $enriched): array
    {
        $requiresReview = (bool) ($payload->metadata['requiresReview'] ?? false);
        $lowConfidence = (array) ($payload->metadata['lowConfidenceFields'] ?? []);
        $overallConfidence = (float) ($payload->metadata['overallConfidence'] ?? 0.0);

        $callerStatus = $payload->context['draftStatus'] ?? null;
        $status = $callerStatus ?? ($requiresReview ? 'pending_review' : 'draft');

        $sku = $payload->context['sku'] ?? $this->generateSku($enriched);
        $channelCode = $payload->context['channelCode'] ?? 'default';
        $localeCode = $payload->context['localeCode'] ?? 'en_US';

        return [
            'sku'         => $sku,
            'status'      => $status,
            'channel'     => $channelCode,
            'locale'      => $localeCode,
            'source'      => 'ai_image_pipeline',
            'imageSource' => $payload->metadata['imageSource'] ?? null,
            'attributes'  => $enriched,
            'aiMeta'      => [
                'agentId'             => $payload->agentId,
                'credentialId'        => $payload->credentialId,
                'overallConfidence'   => $overallConfidence,
                'lowConfidenceFields' => $lowConfidence,
                'confidenceScores'    => $payload->metadata['confidenceScores'] ?? [],
                'visionModel'         => $payload->metadata['visionModel'] ?? null,
                'enrichedKeys'        => $payload->metadata['enrichedKeys'] ?? [],
                'requiresReview'      => $requiresReview,
                'createdAt'           => now()->toISOString(),
            ],
        ];
    }

    /**
     * Persist the draft to the database.
     *
     * Uses the Unopim product draft repository when available in the
     * container, otherwise persists via the generic wk_ai_agent_executions
     * extras column as a lightweight fallback. Integration with a real
     * ProductDraftRepository should override this method.
     *
     * @param  array<string, mixed>  $draft
     */
    protected function persist(array $draft): int|string
    {
        // Primary path: resolve a Webkul product draft repository if bound
        if (app()->bound('Webkul\Product\Repositories\ProductDraftRepository')) {
            /** @var object $repo */
            $repo = app('Webkul\Product\Repositories\ProductDraftRepository');
            $model = $repo->create($draft);

            return $model->id;
        }

        // Fallback: persist inside wk_ai_agent_executions.extras
        // (recorded by LogExecutionStage) — return a deterministic pseudo-ID
        return 'draft_'.md5($draft['sku'].($draft['aiMeta']['createdAt'] ?? ''));
    }

    /**
     * Generate a unique SKU from the product name or a random suffix.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function generateSku(array $attributes): string
    {
        $base = $attributes['name'] ?? $attributes['product_type'] ?? 'product';

        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $base) ?? 'product');
        $slug = trim($slug, '-');

        return substr($slug, 0, 40).'-'.strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }
}
