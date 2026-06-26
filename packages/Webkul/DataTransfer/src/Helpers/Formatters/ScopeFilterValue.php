<?php

namespace Webkul\DataTransfer\Helpers\Formatters;

use Illuminate\Support\Collection;

class ScopeFilterValue
{
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
