<?php

namespace Webkul\Product\Services;

use Webkul\MagicAI\Facades\MagicAI;
use Webkul\Product\Contracts\Suggester;

abstract class AbstractSuggester implements Suggester
{
    abstract public function key(): string;

    abstract public function suggestByRules(array $context): array;

    public function supportsAi(): bool
    {
        return false;
    }

    public function suggestByAi(array $context): array
    {
        $ai = config("suggesters.{$this->key()}.ai", []);

        $magicAi = MagicAI::useDefault()
            ->setSystemPrompt($this->aiSystemPrompt())
            ->setPrompt($this->aiInstruction($context), 'text');

        if (! empty($ai['model'])) {
            $magicAi->setModel($ai['model']);
        }

        if (isset($ai['temperature'])) {
            $magicAi->setTemperature((float) $ai['temperature']);
        }

        if (isset($ai['max_tokens'])) {
            $magicAi->setMaxTokens((int) $ai['max_tokens']);
        }

        $decoded = json_decode($this->extractJson($magicAi->ask()), true);

        return $this->validateAiResult(is_array($decoded) ? $decoded : [], $context);
    }

    /**
     * Sanitize the model's response before it is trusted. Subclasses restrict
     * the keys/values to what the feature actually allows.
     */
    protected function validateAiResult(array $result, array $context): array
    {
        return $result;
    }

    protected function aiSystemPrompt(): string
    {
        return '';
    }

    protected function aiInstruction(array $context): string
    {
        return '';
    }

    protected function extractJson(string $response): string
    {
        $response = trim($response);

        if (preg_match('/\{.*\}/s', $response, $matches)) {
            return $matches[0];
        }

        return $response;
    }
}
