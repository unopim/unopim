<?php

namespace Webkul\DataTransfer\Helpers\Formatters;

class EscapeFormulaOperators
{
    public static $operatorsToEscape = ['=', '-', '+', '@'];

    /**
     * Escape the value by adding a single quote at the beginning and end
     * if it starts with a dangerous operator.
     */
    public static function escapeValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmedValue = ltrim($value);

        if ($trimmedValue !== '' && in_array($trimmedValue[0], self::$operatorsToEscape, true)) {
            return "'".$value."'";
        }

        return $value;
    }

    /**
     * Unescape the value by removing surrounding single quotes if it was escaped.
     */
    public static function unescapeValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmedValue = ltrim($value);

        if (
            strlen($trimmedValue) >= 2
            && in_array($trimmedValue[1], self::$operatorsToEscape, true)
            && $trimmedValue[0] === "'"
            && $trimmedValue[strlen($trimmedValue) - 1] === "'"
        ) {
            return substr($trimmedValue, 1, -1);
        }

        return $value;
    }
}
