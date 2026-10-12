<?php

namespace Webkul\MagicAI\Contracts;

/**
 * Optional capability for LLM model adapters that can return translations
 * as structured output instead of free text. Kept separate from
 * LLMModelInterface so existing third-party implementations remain valid.
 */
interface SupportsStructuredTranslation
{
    /**
     * Translate the configured prompt and return the translated markup.
     */
    public function translate(): string;
}
