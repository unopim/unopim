<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

class FileMimeExtensionMatch implements ValidationRule
{
    /**
     * Validate the file extension and mime type match.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isValidFileInstance($value)) {
            $fail('validation.file')->translate();

            return;
        }

        $extension = $value instanceof UploadedFile ? $value->getClientOriginalExtension() : $value->getExtension();

        $mimeType = $value->getMimeType();

        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($extension);

        if ($mimeTypes === []) {
            $fail(trans('core::validation.file-mime-extension-mismatch', ['extension' => $extension, 'mimeType' => $mimeType]));

            return;
        }

        if (strtolower((string) $extension) === 'jpeg') {
            $extension = 'jpg';
        }

        if (! in_array($mimeType, $mimeTypes) || $value->guessExtension() !== strtolower((string) $extension)) {
            $fail(trans('core::validation.file-mime-extension-mismatch', ['extension' => $extension, 'mimeType' => $mimeType]));

            return;
        }
    }

    /**
     * Check that the given value is a valid file instance.
     *
     * @param  mixed  $value
     */
    public function isValidFileInstance($value): bool
    {
        if ($value instanceof UploadedFile && $value->isValid()) {
            return true;
        }

        return $value instanceof File;
    }
}
