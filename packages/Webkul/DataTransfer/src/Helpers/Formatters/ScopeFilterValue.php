<?php

namespace Webkul\DataTransfer\Helpers\Formatters;

use Illuminate\Support\Collection;

class ScopeFilterValue
{
    /**
     * Normalizes a multiselect scope filter value into a flat array of codes.
     *
     * Accepts every shape the async multiselect may produce: a JSON encoded
     * array of option objects ([{"code": "..."}]), a JSON/plain array of codes,
     * a single value, or a comma separated string.
     */
    public static function toCodes(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }

        return Collection::wrap($value)
            ->map(fn ($item) => is_array($item) ? ($item['code'] ?? null) : trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
