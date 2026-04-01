<?php

namespace Webkul\AiAgent\DTOs;

/**
 * Immutable context DTO that flows through the image→product pipeline.
 *
 * Each stage returns a new instance (via the `with*` mutation helpers)
 * rather than mutating the existing one, guaranteeing a clean, auditable
 * data trail across every step of the pipeline.
 *
 * Stage-to-field mapping:
 *   ImageUploadStep      → imagePath
 *   VisionDetectionStep  → detectedProduct, rawAiResponse
 *   AttributeMappingStep → attributes, category
 *   EnrichmentStep       → enrichment
 *   ConfidenceScoreStep  → confidence
 *   CreateProductDraftStep → productId
 */
final class ImageProductContext
{
    /**
     * @param  string|null  $imagePath  Resolved local path or remote URL of the image.
     * @param  string|null  $detectedProduct  Product type / name inferred by the vision AI.
     * @param  array<string, mixed>  $attributes  PIM attribute map (code → value) after mapping stage.
     * @param  string|null  $category  Primary PIM category code suggested for the product.
     * @param  array<string, mixed>  $enrichment  AI-generated fields added during the enrichment stage.
     * @param  array<string, float>  $confidence  Per-attribute confidence scores (0.0 – 1.0).
     * @param  string|null  $rawAiResponse  Raw text response returned by the vision AI call.
     * @param  int|string|null  $productId  ID of the product draft created in the final stage.
     */
    public function __construct(
        public readonly ?string $imagePath = null,
        public readonly ?string $detectedProduct = null,
        public readonly array $attributes = [],
        public readonly ?string $category = null,
        public readonly array $enrichment = [],
        public readonly array $confidence = [],
        public readonly ?string $rawAiResponse = null,
        public readonly int|string|null $productId = null,
    ) {}

    // -------------------------------------------------------------------------
    // Mutation helpers — each returns a new instance
    // -------------------------------------------------------------------------

    /**
     * Set (or replace) the resolved image path / URL.
     */
    public function withImagePath(string $imagePath): self
    {
        return new self(
            imagePath: $imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: $this->attributes,
            category: $this->category,
            enrichment: $this->enrichment,
            confidence: $this->confidence,
            rawAiResponse: $this->rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Set the detected product type / name.
     */
    public function withDetectedProduct(string $detectedProduct): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $detectedProduct,
            attributes: $this->attributes,
            category: $this->category,
            enrichment: $this->enrichment,
            confidence: $this->confidence,
            rawAiResponse: $this->rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Merge additional PIM attributes into the existing map.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: array_merge($this->attributes, $attributes),
            category: $this->category,
            enrichment: $this->enrichment,
            confidence: $this->confidence,
            rawAiResponse: $this->rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Set the primary PIM category code.
     */
    public function withCategory(string $category): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: $this->attributes,
            category: $category,
            enrichment: $this->enrichment,
            confidence: $this->confidence,
            rawAiResponse: $this->rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Merge AI-enriched fields (name, SEO copy, tagline, etc.) into the
     * existing enrichment map.
     *
     * @param  array<string, mixed>  $enrichment
     */
    public function withEnrichment(array $enrichment): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: $this->attributes,
            category: $this->category,
            enrichment: array_merge($this->enrichment, $enrichment),
            confidence: $this->confidence,
            rawAiResponse: $this->rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Replace (or merge) per-attribute confidence scores.
     *
     * @param  array<string, float>  $confidence
     */
    public function withConfidence(array $confidence): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: $this->attributes,
            category: $this->category,
            enrichment: $this->enrichment,
            confidence: array_merge($this->confidence, $confidence),
            rawAiResponse: $this->rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Store the raw AI response text.
     */
    public function withRawAiResponse(string $rawAiResponse): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: $this->attributes,
            category: $this->category,
            enrichment: $this->enrichment,
            confidence: $this->confidence,
            rawAiResponse: $rawAiResponse,
            productId: $this->productId,
        );
    }

    /**
     * Set the persisted product draft ID.
     */
    public function withProductId(int|string $productId): self
    {
        return new self(
            imagePath: $this->imagePath,
            detectedProduct: $this->detectedProduct,
            attributes: $this->attributes,
            category: $this->category,
            enrichment: $this->enrichment,
            confidence: $this->confidence,
            rawAiResponse: $this->rawAiResponse,
            productId: $productId,
        );
    }

    // -------------------------------------------------------------------------
    // Computed helpers
    // -------------------------------------------------------------------------

    /**
     * Return the overall (mean) confidence score across all attribute scores.
     * Returns 0.0 when no scores are present.
     */
    public function overallConfidence(): float
    {
        if (empty($this->confidence)) {
            return 0.0;
        }

        return round(array_sum($this->confidence) / count($this->confidence), 4);
    }

    /**
     * Return attribute keys whose confidence score falls below the given
     * threshold (default: 0.6).
     *
     * @return array<string>
     */
    public function lowConfidenceFields(float $threshold = 0.6): array
    {
        return array_keys(
            array_filter($this->confidence, fn (float $score) => $score < $threshold),
        );
    }

    /**
     * Return the merged product data — attributes enriched with the
     * enrichment layer values (enrichment takes lower priority, attributes win).
     *
     * @return array<string, mixed>
     */
    public function resolvedAttributes(): array
    {
        return array_merge($this->enrichment, $this->attributes);
    }

    /**
     * Determine whether the draft requires manual review.
     */
    public function requiresReview(float $threshold = 0.6): bool
    {
        return ! empty($this->lowConfidenceFields($threshold));
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * Deserialise from a plain array (e.g. queued job payload).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            imagePath: isset($data['image_path']) ? (string) $data['image_path'] : null,
            detectedProduct: isset($data['detected_product']) ? (string) $data['detected_product'] : null,
            attributes: (array) ($data['attributes'] ?? []),
            category: isset($data['category']) ? (string) $data['category'] : null,
            enrichment: (array) ($data['enrichment'] ?? []),
            confidence: array_map('floatval', (array) ($data['confidence'] ?? [])),
            rawAiResponse: isset($data['raw_ai_response']) ? (string) $data['raw_ai_response'] : null,
            productId: $data['product_id'] ?? null,
        );
    }

    /**
     * Serialise to a plain array using snake_case keys (matches DB column names).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'image_path'       => $this->imagePath,
            'detected_product' => $this->detectedProduct,
            'attributes'       => $this->attributes,
            'category'         => $this->category,
            'enrichment'       => $this->enrichment,
            'confidence'       => $this->confidence,
            'raw_ai_response'  => $this->rawAiResponse,
            'product_id'       => $this->productId,
        ];
    }
}
