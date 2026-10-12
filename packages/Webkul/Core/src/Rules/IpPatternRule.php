<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IpPatternRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isIpPattern($value)) {
            $fail('core::validation.ip-address-list')->translate();
        }
    }

    /**
     * Determine if the value is a comma separated list of IPv4/IPv6 addresses.
     *
     * @param  mixed  $value
     */
    public function isIpPattern($value): bool
    {
        $addresses = array_map(trim(...), explode(',', (string) $value));

        return array_all(
            $addresses,
            fn ($address): bool => filter_var($address, FILTER_VALIDATE_IP) !== false
        );
    }
}
