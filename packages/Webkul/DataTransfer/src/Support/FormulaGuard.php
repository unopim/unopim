<?php

namespace Webkul\DataTransfer\Support;

/**
 * Neutralises spreadsheet formula injection in exported cell values.
 *
 * A cell opening with `=`, `+`, `-`, `@`, a tab or a carriage return is parsed
 * as a formula by Excel and LibreOffice, which is how a stored product name
 * becomes DDE command execution on the machine that opens the download.
 */
class FormulaGuard
{
    /**
     * Characters that make a spreadsheet application treat a cell as a formula.
     *
     * @var list<string>
     */
    protected const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Determine whether a value would be interpreted as a formula.
     */
    public static function isFormula(string $value): bool
    {
        if ($value === '' || is_numeric($value)) {
            return false;
        }

        if (in_array($value[0], self::TRIGGERS, true)) {
            return true;
        }

        $trimmed = ltrim($value, " \t\r\n");

        return $trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true);
    }

    /**
     * Prefix a formula-shaped value so it is read as text, leaving others as-is.
     */
    public static function escape(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return self::isFormula($value) ? "'".$value : $value;
    }
}
