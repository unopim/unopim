<?php

namespace Webkul\MagicAI\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Structured-output agent for Magic AI translations.
 *
 * Returns the translated markup in a typed `translated_html` field so the
 * caller never has to scrape the model's free-text reply with regexes.
 */
class TranslationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected string $systemPrompt = '',
    ) {}

    public function instructions(): string
    {
        return $this->systemPrompt !== ''
            ? $this->systemPrompt
            : 'You are a precise translator. Translate the given content, preserving the original HTML structure (every <p>, <br>, list and inline tag) exactly. Do not add commentary, wrappers, or extra text.';
    }

    /**
     * The structured output schema for the translation.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'translated_html' => $schema->string()
                ->description('The translated content with the original HTML structure preserved.')
                ->required(),
        ];
    }
}
