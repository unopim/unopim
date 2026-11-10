<?php

namespace Webkul\Core\Traits;

use Webkul\Core\Rules\Decimal;

/**
 * Turns a field's `validation` / `regex_pattern` settings into rules: one entry
 * of the client-side rules object the admin forms bind with `::rules`, and one
 * server-side Laravel rule.
 *
 * Both settings are editable data, so neither may become code or an arbitrary
 * rule. The client-side object is compiled by Vue as an expression, so the
 * pattern is emitted as a JSON string literal with quotes, markup and
 * apostrophes hex-escaped, and a validation type that is not a plain identifier
 * is dropped. Server-side, only the known validation types map to a rule; any
 * other stored value is ignored instead of being handed to the validator,
 * where something like `exists:admin_users,email` would run a query.
 *
 * @property string|null $validation
 * @property string|null $regex_pattern
 */
trait ValidationSettingRules
{
    /**
     * The rules-object entry for the configured validation, or null when
     * there is none to render.
     */
    protected function clientValidationRule(): ?string
    {
        if (! $this->validation) {
            return null;
        }

        return match ($this->validation) {
            'regex'  => 'regex: '.json_encode((string) $this->regex_pattern, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'number' => 'numeric: true',
            default  => preg_match('/^[a-z_]+$/', (string) $this->validation) === 1 ? $this->validation.': true' : null,
        };
    }

    /**
     * The server-side rule for the configured validation, or null when there
     * is none or the stored type is not one this application offers.
     */
    protected function serverValidationRule(): string|Decimal|null
    {
        return match ($this->validation) {
            'regex'        => 'regex: "'.$this->regex_pattern.'"',
            'number'       => 'numeric',
            'decimal'      => new Decimal,
            'email', 'url' => $this->validation,
            default        => null,
        };
    }
}
