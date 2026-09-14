<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Webkul\Core\Helpers\MediaMimeTypes;

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

        $normalizedExtension = self::normalizeExtension($extension);

        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($normalizedExtension);

        if ($mimeTypes === []) {
            $fail(trans('core::validation.file-mime-extension-mismatch', ['extension' => $extension, 'mimeType' => $mimeType]));

            return;
        }

        if (! in_array($mimeType, $mimeTypes, true) || self::normalizeExtension($value->guessExtension() ?? '') !== $normalizedExtension) {
            $fail(trans('core::validation.file-mime-extension-mismatch', ['extension' => $extension, 'mimeType' => $mimeType]));

            return;
        }
    }

    public static function normalizeExtension(string $extension): string
    {
        return MediaMimeTypes::normalizeExtension($extension);
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
