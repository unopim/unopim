<?php

namespace Webkul\Core\Traits;

trait ResolvesClientValidationRule
{
    /**
     * Map the configured field validation to its vee-validate rule.
     *
     * Number maps to the client decimal rule because vee-validate's numeric rule only
     * accepts unsigned integers, while the server-side numeric rule accepts signed decimals.
     */
    protected function clientValidationRule(): string
    {
        return match ($this->validation) {
            'regex'  => 'regex: "'.$this->regex_pattern.'"',
            'number' => 'decimal: true',
            default  => $this->validation.': true',
        };
    }
}
