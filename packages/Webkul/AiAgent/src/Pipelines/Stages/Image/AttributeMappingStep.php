<?php

namespace Webkul\AiAgent\Pipelines\Stages\Image;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\PipelineException;

/**
 * Stage 3 — Maps raw vision detections to the Unopim PIM attribute schema.
 *
 * This stage is intentionally free of AI calls: it applies rule-based
 * mapping so that the output is deterministic and fully testable.
 *
 * Reads from metadata (set by VisionDetectionStep):
 *   visionDetections  — raw AI detection fields
 *
 * Reads from context (optional):
 *   attributeMap  array  Custom field → attribute mappings
 *                        e.g. ['productType' => 'pim_product_type']
 *
 * Writes to metadata:
 *   mappedAttributes   array  PIM-keyed attribute values
 *   unmappedFields     array  Detection fields that had no mapping rule
 */
class AttributeMappingStep implements PipelineStageContract
{
    /**
     * Default mapping: vision detection key → PIM attribute code.
     *
     * @var array<string, string>
     */
    protected const DEFAULT_MAP = [
        'productType'        => 'product_type',
        'colors'             => 'color',
        'materials'          => 'material',
        'patterns'           => 'pattern',
        'style'              => 'style',
        'condition'          => 'condition',
        'brandIndicators'    => 'brand',
        'suggestedCategories'=> 'categories',
        'rawDescription'     => 'description',
    ];

    /**
     * Fields whose values should be joined to a comma-separated string.
     *
     * @var array<string>
     */
    protected const ARRAY_JOIN_FIELDS = [
        'colors',
        'materials',
        'patterns',
        'brandIndicators',
    ];

    /**
     * {@inheritdoc}
     *
     * @throws PipelineException
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $detections = $payload->metadata['visionDetections'] ?? null;

        if (! is_array($detections) || empty($detections)) {
            throw new PipelineException(
                'AttributeMappingStep: visionDetections is missing — VisionDetectionStep must run first.',
                self::class,
            );
        }

        // Merge default map with any caller-supplied overrides
        $map = array_merge(
            self::DEFAULT_MAP,
            (array) ($payload->context['attributeMap'] ?? []),
        );

        $mappedAttributes = [];
        $unmappedFields = [];

        foreach ($detections as $detectionKey => $value) {
            if (isset($map[$detectionKey])) {
                $pimKey = $map[$detectionKey];

                $mappedAttributes[$pimKey] = $this->castValue($detectionKey, $value);
            } else {
                $unmappedFields[$detectionKey] = $value;
            }
        }

        $ctx = ImageProductContext::fromArray($payload->metadata['imageContext'] ?? [])
            ->withAttributes($mappedAttributes);

        // Promote mapped category if not already set
        if (empty($ctx->category) && ! empty($mappedAttributes['categories'])) {
            $firstCategory = is_array($mappedAttributes['categories'])
                ? ($mappedAttributes['categories'][0] ?? null)
                : $mappedAttributes['categories'];

            if ($firstCategory !== null) {
                $ctx = $ctx->withCategory((string) $firstCategory);
            }
        }

        return $next($payload->withMetadata([
            'imageContext'     => $ctx->toArray(),
            'mappedAttributes' => $mappedAttributes,
            'unmappedFields'   => $unmappedFields,
        ]));
    }

    /**
     * Cast a detection value to the appropriate scalar or simple type
     * expected by the PIM attribute.
     */
    protected function castValue(string $detectionKey, mixed $value): mixed
    {
        // Arrays in join-list → comma string (e.g. color: "blue, white")
        if (in_array($detectionKey, self::ARRAY_JOIN_FIELDS, true) && is_array($value)) {
            return implode(', ', array_filter($value));
        }

        // suggestedCategories — keep as array for multi-select
        if ($detectionKey === 'suggestedCategories' && is_array($value)) {
            return $value;
        }

        return $value;
    }
}
