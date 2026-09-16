<?php

namespace Webkul\MagicAI\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use stdClass;
use Webkul\MagicAI\Services\ProviderOverrides;

/**
 * Guards the free-form `extras` payload merged into a laravel/ai provider
 * config. Rejects anything that would redefine the endpoint or credential
 * the platform record already owns and validates separately.
 */
class SafeProviderExtras implements ValidationRule
{
    protected const MAX_BYTES = 8192;

    protected const MAX_DEPTH = 5;

    /**
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, [null, '', []], true)) {
            return;
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, self::MAX_DEPTH);
        }

        if (! is_string($value)) {
            $fail(trans('admin::app.configuration.platform.message.extras-invalid-json'));

            return;
        }

        if (strlen($value) > self::MAX_BYTES) {
            $fail(trans('admin::app.configuration.platform.message.extras-too-large'));

            return;
        }

        $value = json_decode($value, false, self::MAX_DEPTH);

        if (json_last_error() !== JSON_ERROR_NONE || ! $value instanceof stdClass) {
            $fail(trans('admin::app.configuration.platform.message.extras-invalid-json'));

            return;
        }

        $reserved = array_intersect(
            array_map(strtolower(...), array_keys((array) $value)),
            ProviderOverrides::RESERVED_KEYS,
        );

        if ($reserved !== []) {
            $fail(trans('admin::app.configuration.platform.message.extras-reserved-key', [
                'keys' => implode(', ', $reserved),
            ]));
        }
    }
}
