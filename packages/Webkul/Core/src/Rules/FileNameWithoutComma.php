<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class FileNameWithoutComma implements ValidationRule
{
    /**
     * Refuse an uploaded file, or any file of an uploaded list, whose name contains a comma, since stored media lists are comma separated.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (is_array($value) ? $value : [$value] as $file) {
            if ($file instanceof UploadedFile && str_contains($file->getClientOriginalName(), ',')) {
                $fail(trans('core::validation.file-name-comma', ['attribute' => $attribute]));

                return;
            }
        }
    }
}
