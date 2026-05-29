<?php

namespace Webkul\MagicAI\Services\Prompt;

use Illuminate\Support\Str;

abstract class AbstractPrompt
{
    abstract protected function updatePrompt(string $prompt, int $resourceId): string;

    // Implement the searchStringWithAt method
    protected function searchStringWithAt(string $string): array
    {
        return Str::matchAll('/@\w+/', $string)->toArray();
    }

    public function getValue(array $values, string $key): mixed
    {
        return $values[$key] ?? '';
    }
}
