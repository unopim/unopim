<?php

namespace Webkul\Product\Contracts;

interface Suggester
{
    /**
     * Unique key of this suggester (matches a config/suggesters.php entry).
     */
    public function key(): string;

    /**
     * Whether this suggester can produce AI-backed suggestions.
     */
    public function supportsAi(): bool;

    /**
     * Rule-based suggestion. Always available, no external calls.
     *
     * @return array<string, mixed>
     */
    public function suggestByRules(array $context): array;

    /**
     * AI-backed suggestion via the configured MagicAI provider.
     *
     * @return array<string, mixed>
     */
    public function suggestByAi(array $context): array;
}
