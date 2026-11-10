<?php

namespace Webkul\Core\Traits;

/**
 * Renders a field's `validation` / `regex_pattern` settings as one entry of
 * the client-side rules object the admin forms bind with `::rules`.
 *
 * That object is compiled by Vue as an expression, so both settings must stay
 * data: the pattern is emitted as a JSON string literal with quotes, markup
 * and apostrophes hex-escaped, and a validation type that is not a plain
 * identifier is dropped rather than emitted as an object key.
 *
 * @property string|null $validation
 * @property string|null $regex_pattern
 */
trait ClientValidationRule
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
}
