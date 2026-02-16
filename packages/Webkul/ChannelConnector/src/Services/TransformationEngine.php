<?php

namespace Webkul\ChannelConnector\Services;

class TransformationEngine
{
    /**
     * Apply a pipeline of transformation rules to a value.
     *
     * Each rule has a 'type' and optional 'config' array.
     */
    public static function apply(mixed $value, array $rules): mixed
    {
        foreach ($rules as $rule) {
            $type = $rule['type'] ?? null;
            $config = $rule['config'] ?? [];

            if (! $type) {
                continue;
            }

            $value = match ($type) {
                'uppercase'         => self::uppercase($value),
                'lowercase'         => self::lowercase($value),
                'capitalize'        => self::capitalize($value),
                'prefix'            => self::prefix($value, $config),
                'suffix'            => self::suffix($value, $config),
                'replace'           => self::replace($value, $config),
                'number_format'     => self::numberFormat($value, $config),
                'currency_convert'  => self::currencyConvert($value, $config),
                'map_values'        => self::mapValues($value, $config),
                'truncate'          => self::truncate($value, $config),
                'strip_html'        => self::stripHtml($value),
                'default_value'     => self::defaultValue($value, $config),
                'markup_percentage' => self::markupPercentage($value, $config),
                'markup_fixed'      => self::markupFixed($value, $config),
                'round_price'       => self::roundPrice($value, $config),
                default             => $value,
            };
        }

        return $value;
    }

    protected static function uppercase(mixed $value): mixed
    {
        return is_string($value) ? mb_strtoupper($value) : $value;
    }

    protected static function lowercase(mixed $value): mixed
    {
        return is_string($value) ? mb_strtolower($value) : $value;
    }

    protected static function capitalize(mixed $value): mixed
    {
        return is_string($value) ? mb_convert_case($value, MB_CASE_TITLE) : $value;
    }

    protected static function prefix(mixed $value, array $config): mixed
    {
        $prefix = $config['value'] ?? '';

        return is_string($value) ? $prefix.$value : $value;
    }

    protected static function suffix(mixed $value, array $config): mixed
    {
        $suffix = $config['value'] ?? '';

        return is_string($value) ? $value.$suffix : $value;
    }

    protected static function replace(mixed $value, array $config): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $search = $config['search'] ?? '';
        $replacement = $config['replacement'] ?? '';

        return str_replace($search, $replacement, $value);
    }

    protected static function numberFormat(mixed $value, array $config): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $decimals = $config['decimals'] ?? 2;
        $decimalSeparator = $config['decimal_separator'] ?? '.';
        $thousandsSeparator = $config['thousands_separator'] ?? '';

        return number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    protected static function currencyConvert(mixed $value, array $config): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $rate = $config['rate'] ?? 1;

        return round((float) $value * (float) $rate, 4);
    }

    protected static function mapValues(mixed $value, array $config): mixed
    {
        $mapping = $config['mapping'] ?? [];
        $stringValue = is_string($value) ? $value : (string) $value;

        return $mapping[$stringValue] ?? $value;
    }

    protected static function truncate(mixed $value, array $config): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $maxLength = $config['max_length'] ?? 255;
        $ellipsis = $config['ellipsis'] ?? '';

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength - mb_strlen($ellipsis)).$ellipsis;
    }

    protected static function stripHtml(mixed $value): mixed
    {
        return is_string($value) ? strip_tags($value) : $value;
    }

    protected static function defaultValue(mixed $value, array $config): mixed
    {
        $default = $config['value'] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    protected static function markupPercentage(mixed $value, array $config): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $percentage = (float) ($config['percentage'] ?? 0);

        return round((float) $value * (1 + $percentage / 100), 10);
    }

    protected static function markupFixed(mixed $value, array $config): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $amount = (float) ($config['amount'] ?? 0);

        return (float) $value + $amount;
    }

    protected static function roundPrice(mixed $value, array $config): mixed
    {
        if (! is_numeric($value)) {
            return $value;
        }

        $precision = (int) ($config['precision'] ?? 2);
        $strategy = $config['strategy'] ?? 'round';

        $floatValue = (float) $value;

        return match ($strategy) {
            'ceil'     => ceil($floatValue * (10 ** $precision)) / (10 ** $precision),
            'floor'    => floor($floatValue * (10 ** $precision)) / (10 ** $precision),
            'round_99' => floor($floatValue) + 0.99,
            default    => round($floatValue, $precision),
        };
    }
}
