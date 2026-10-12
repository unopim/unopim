<?php

namespace Webkul\AiAgent\Services\VectorStore;

/**
 * Builds the embeddable text document (and its content hash) for a product.
 *
 * Pure — no database or network access — so batching jobs and tests can use it
 * without side effects.
 */
class ProductEmbeddingDocumentBuilder
{
    /**
     * Build the embedding payload for a single product row.
     *
     * @param  array<string, mixed>|string|null  $values  decoded (or raw JSON) product values
     * @return array{product_id: int, sku: ?string, text: string, content_hash: string}
     */
    public function build(int $productId, ?string $sku, array|string|null $values): array
    {
        if (is_string($values)) {
            $values = json_decode($values, true);
        }

        $parts = [];

        if ($sku !== null && trim($sku) !== '') {
            $parts[] = 'sku: '.trim($sku);
        }

        if (is_array($values)) {
            $parts = array_merge($parts, $this->collectTextualValues($values));
        }

        $text = implode("\n", array_values(array_unique($parts)));

        $maxLength = max(100, (int) config('ai-agent.vector_store.max_document_length', 6000));

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return [
            'product_id'   => $productId,
            'sku'          => $sku,
            'text'         => $text,
            'content_hash' => hash('sha256', $text),
        ];
    }

    /**
     * Recursively collect scalar textual attribute values as "code: value" lines.
     *
     * @param  array<string|int, mixed>  $values
     * @return array<int, string>
     */
    protected function collectTextualValues(array $values): array
    {
        $lines = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $lines = array_merge($lines, $this->collectTextualValues($value));

                continue;
            }

            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }

            $value = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5));

            if ($value === '') {
                continue;
            }

            $lines[] = is_string($key) && $key !== ''
                ? $key.': '.$value
                : $value;
        }

        return $lines;
    }
}
