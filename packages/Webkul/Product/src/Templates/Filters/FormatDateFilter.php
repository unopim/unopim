<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Webkul\Attribute\Models\Attribute;

class FormatDateFilter implements TemplateFilter
{
    private const string FORMAT_PATTERN = '/^[dDjlNSwzWFmMntLoXxYyaABgGhHisuveIOPpTZcrU .,:\/\-]{1,30}$/';

    public function supports(string $attributeType): bool
    {
        return in_array($attributeType, [Attribute::DATE_FIELD_TYPE, Attribute::DATETIME_FIELD_TYPE], true);
    }

    public function acceptsArgument(?string $argument): bool
    {
        return $argument !== null && preg_match(self::FORMAT_PATTERN, $argument) === 1;
    }

    public function apply(string $value, ?string $argument, string $locale): ?string
    {
        if (! $this->acceptsArgument($argument)) {
            return null;
        }

        try {
            return Carbon::parse($value)->locale($locale)->translatedFormat($argument);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
