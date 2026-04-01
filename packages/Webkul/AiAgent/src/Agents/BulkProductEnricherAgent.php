<?php

namespace Webkul\AiAgent\Agents;

use Webkul\AiAgent\DTOs\AgentResult;

/**
 * Command to execute an ImageProductAgent and persist results.
 *
 * Example:
 *   php artisan ai-agent:analyze-image https://example.com/product.jpg --agent-id=1 --credential-id=1
 */
class BulkProductEnricherAgent extends BaseAgent
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
            You are a bulk product data enrichment expert.
            Analyze multiple products and enrich their data with missing information.
            Return valid JSON array where each item contains:
            - sku: string (product SKU)
            - name: string (enriched name)
            - description: string (enriched description)
            - category: string (assigned category)
            - tags: array of strings (enrichment tags)
            - missingFields: array of strings (fields that need manual entry)
            - qualityScore: number (0-100, data completeness)
            PROMPT;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildInstruction(mixed $input): string
    {
        $products = is_array($input)
            ? json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : (string) $input;

        return "Enrich this product data batch:\n\n".$products;
    }

    /**
     * Execute enrichment on a batch of products.
     *
     * @param  array<int, array<string, mixed>>  $products  Array of product data
     * @param  array<string, mixed>  $context
     */
    public function enrichBatch(
        array $products,
        int $agentId,
        int $credentialId,
        array $context = [],
    ): AgentResult {
        return $this->execute(
            input: [
                'productCount' => count($products),
                'products'     => $products,
            ],
            agentId: $agentId,
            credentialId: $credentialId,
            context: array_merge(['batchOperation' => true], $context),
        );
    }
}
