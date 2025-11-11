<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Concerns\ValidatesAttributes;

class FileOrImageValidValue implements ValidationRule
{
    use ValidatesAttributes;

    const FILE_ALLOWED_EXTENSION = ['pdf'];

    const IMAGE_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];

    protected FileMimeExtensionMatch $fileExtensionMatchRule;

    public function __construct(
        protected bool $isImage = false,
        protected array $allowedMimes = [],
        protected array $allowedExtensions = [],
        protected bool $isMultiple = false
    ) {
        $this->allowedExtensions = $allowedExtensions ?: (
            $this->isImage ? self::IMAGE_ALLOWED_EXTENSIONS : self::FILE_ALLOWED_EXTENSION
        );

        $this->allowedMimes = $allowedMimes ?: (
            $this->isImage ? self::IMAGE_ALLOWED_EXTENSIONS : self::FILE_ALLOWED_EXTENSION
        );

        $this->fileExtensionMatchRule = new FileMimeExtensionMatch;
    }

    /**
     * Public method to merge additional extensions.
     */
    public function mergeAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = array_unique(array_merge($this->allowedExtensions, $extensions));

        return $this;
    }

    /**
     * Public method to merge additional mimes.
     */
    public function mergeAllowedMimes(array $mimes): self
    {
        $this->allowedMimes = array_unique(array_merge($this->allowedMimes, $mimes));

        return $this;
    }

    /**
     * Validate the file extension and mime type match.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // For gallery attribute during import which has comma separated values
        if ($this->isMultiple && is_string($value) && str_contains($value, ',')) {
            $value = array_filter(explode(',', $value), 'trim');
        }

        $this->validateFileOrPath($attribute, $value, $fail);

        if (is_array($value)) {
            foreach ($value as $fileOrPath) {
                $this->validateFileOrPath($attribute, $fileOrPath, $fail);
            }
        }
    }

    public function validateFileOrPath(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->isValidFileInstance($value)) {
            if (! $this->validateMimeAndExtension($attribute, $value, $fail)) {
                return;
            }

            $this->fileExtensionMatchRule->validate($attribute, $value, $fail);
        }

        if (is_string($value) && ! Storage::exists($value)) {
            $fail('core::validation.file-not-exists')->translate(['value' => $value]);
        }
    }

    protected function validateMimeAndExtension(string $attribute, UploadedFile|File $value, Closure $fail): bool
    {
        $extension = $value instanceof UploadedFile ? $value->getClientOriginalExtension() : $value->getExtension();

        if ($this->allowedExtensions && ! in_array($extension, $this->allowedExtensions, true)) {
            $fail('validation.extensions')->translate(['values' => implode(', ', $this->allowedExtensions)]);

            return false;
        }

        if ($this->allowedMimes && ! $this->validateMimes($attribute, $value, $this->allowedMimes)) {
            $fail('validation.mimes')->translate(['values' => implode(', ', $this->allowedMimes)]);

            return false;
        }

        return true;
    }
}
