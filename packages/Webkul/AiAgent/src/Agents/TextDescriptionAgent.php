<?php

namespace Webkul\AiAgent\Agents;

/**
 * Concrete agent for generating product descriptions from text input.
 *
 * Accepts a product title and specifications, generates
 * compelling product descriptions and marketing copy.
 *
 * Usage:
 *   $agent = app(TextDescriptionAgent::class);
 *   $result = $agent->execute(
 *       input: 'Running Shoes - Nike Air Max',
 *       agentId: 2,
 *       credentialId: 1,
 *       context: ['locale' => 'en_US', 'brand' => 'Nike'],
 *   );
 */
class TextDescriptionAgent extends BaseAgent
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
            You are an expert product copywriter and marketer.
            Generate compelling product descriptions, titles, and marketing content.
            Return valid JSON with:
            - productName: string (optimized product name)
            - shortDescription: string (1-2 sentences)
            - longDescription: string (detailed, benefit-focused)
            - keyBenefits: array of strings (top 5 benefits)
            - targetAudience: string (who is this for?)
            - marketingTagline: string (catchy tagline)
            - seoKeywords: array of strings (relevant keywords)
            - sellingPoints: array of strings (unique selling points)
            PROMPT;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildInstruction(mixed $input): string
    {
        if (is_array($input)) {
            $details = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return "Generate product descriptions for:\n\n".$details;
        }

        return 'Generate compelling product descriptions for: '.(string) $input;
    }
}
