<?php

namespace Webkul\Core\Helpers;

class ClientValidationRules
{
    /**
     * Encode VeeValidate rules as a JSON object literal that is safe to place inside a Vue binding.
     *
     * Quotes, apostrophes, angle brackets and ampersands inside values are emitted as unicode escapes,
     * so admin-entered values such as a regex pattern stay a string literal and cannot break out of it.
     *
     * @param  array<string, mixed>  $rules
     */
    public static function encode(array $rules): string
    {
        return json_encode(
            (object) $rules,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
