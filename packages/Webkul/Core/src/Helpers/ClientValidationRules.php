<?php

namespace Webkul\Core\Helpers;

class ClientValidationRules
{
    /**
     * Mime types the client accepts for an image field.
     */
    public const IMAGE_MIMES = ['image/bmp', 'image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    /**
     * Build the encoded client-side rules shared by attributes, category fields and association fields.
     *
     * A non-positive upload size leaves the size and mimes rules out, as for an unset upload limit.
     *
     * @param  array<string, mixed>  $typeRules  rules specific to the field type, such as `decimal` for a price
     */
    public static function forField(
        bool $isRequired,
        ?string $type,
        int $uploadKilobytes,
        ?string $validation,
        ?string $regexPattern,
        array $typeRules = [],
    ): string {
        $rules = $isRequired ? ['required' => true] : [];

        $rules += $typeRules;

        if (in_array($type, ['file', 'image'], true) && $uploadKilobytes > 0) {
            $rules['size'] = $uploadKilobytes;

            if ($type === 'image') {
                $rules['mimes'] = self::IMAGE_MIMES;
            }
        }

        if ($validation) {
            $rules += match ($validation) {
                'regex'  => ['regex' => (string) $regexPattern],
                'number' => ['numeric' => true],
                default  => [$validation => true],
            };
        }

        return self::encode($rules);
    }

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
