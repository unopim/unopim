<?php

namespace Webkul\AiAgent\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use JsonException;

class BoundedJson implements ValidationRule
{
    public function __construct(private int $maxBytes = 16384, private int $maxDepth = 8, private int $maxNodes = 2048) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nodes = 0;

        if (! $this->bounded($value, 0, $nodes)) {
            $fail(trans('ai-agent::app.common.invalid-data'));

            return;
        }

        try {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $fail(trans('ai-agent::app.common.invalid-data'));

            return;
        }

        if (strlen($encoded) > $this->maxBytes) {
            $fail(trans('ai-agent::app.common.invalid-data'));
        }
    }

    private function bounded(mixed $value, int $depth, int &$nodes): bool
    {
        if (++$nodes > $this->maxNodes || $depth > $this->maxDepth) {
            return false;
        }

        if (! is_array($value)) {
            return $value === null || is_scalar($value);
        }

        foreach ($value as $key => $child) {
            if (strlen((string) $key) > 128
                || in_array($key, ['__proto__', 'prototype', 'constructor'], true)
                || ! $this->bounded($child, $depth + 1, $nodes)) {
                return false;
            }
        }

        return true;
    }
}
