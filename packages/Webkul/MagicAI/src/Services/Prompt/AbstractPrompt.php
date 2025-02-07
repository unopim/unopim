<?php

namespace Webkul\MagicAI\Services\Prompt;

use Illuminate\Support\Str;

abstract class AbstractPrompt
{
    abstract protected function updatePrompt(string $prompt, int $resourceId);

    // Implement the searchStringWithAt method
    protected function searchStringWithAt($string)
    {
        $matches = Str::matchAll('/@\w+/', $string)->toArray();

        return $matches;
    }

    public function getValue(array $values, string $key)
    {
        return $values[$key] ?? '';
    }
}
