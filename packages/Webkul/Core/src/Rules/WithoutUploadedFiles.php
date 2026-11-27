<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects an uploaded file anywhere inside a value, at any nesting depth.
 *
 * Endpoints that store media through a dedicated upload route use this to keep
 * files out of generic payloads, where the extension and content checks of that
 * route would never run.
 */
class WithoutUploadedFiles implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pending = [$value];

        while ($pending !== []) {
            $item = array_pop($pending);

            if ($item instanceof UploadedFile) {
                $fail('core::validation.uploaded-file-not-allowed')->translate();

                return;
            }

            if (is_array($item)) {
                array_push($pending, ...array_values($item));
            }
        }
    }
}
