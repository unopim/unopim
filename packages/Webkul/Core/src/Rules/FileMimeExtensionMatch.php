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

        if (empty($mimeTypes)) {
            $fail(trans('core::validation.file-mime-extension-mismatch', ['extension' => $extension, 'mimeType' => $mimeType]));

            return;
        }

        if (! (in_array($mimeType, $mimeTypes) && $value->guessExtension() === $extension)) {
            $fail(trans('core::validation.file-mime-extension-mismatch', ['extension' => $extension, 'mimeType' => $mimeType]));

            return;
        }
    }

    /**
     * Check that the given value is a valid file instance.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValidFileInstance($value)
    {
        return ($value instanceof UploadedFile && $value->isValid()) || $value instanceof File;
    }
}
