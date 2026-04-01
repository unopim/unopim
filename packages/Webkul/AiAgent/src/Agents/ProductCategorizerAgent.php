<?php

namespace Webkul\AiAgent\Agents;

/**
 * Concrete agent for categorizing and tagging products.
 *
 * Accepts product details and automatically assigns categories,
 * tags, and attributes based on intelligent analysis.
 *
 * Usage:
 *   $agent = app(ProductCategorizerAgent::class);
 *   $result = $agent->execute(
 *       input: ['name' => 'Blue Cotton T-Shirt', 'price' => 29.99],
 *       agentId: 3,
 *       credentialId: 1,
 *   );
 */
class ProductCategorizerAgent extends BaseAgent
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
            You are an expert e-commerce product categorization AI.
            Analyze product information and assign appropriate categories and tags.
            Return valid JSON with:
            - primaryCategory: string (main product category)
            - secondaryCategories: array of strings (related categories)
            - tags: array of strings (searchable tags)
            - attributes: object with key-value pairs (color, size, material, etc.)
            - nsfwContent: boolean (explicit content?)
            - confidenceLevel: string (high, medium, low)
            - suggestedActions: array of strings (recommended next steps)
            PROMPT;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildInstruction(mixed $input): string
    {
        $details = is_array($input)
            ? json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : (string) $input;

        return "Categorize and tag this product:\n\n".$details;
    }
}
