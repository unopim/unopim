<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Symfony\Component\Mime\MimeTypes;

class FileOrImageValidValue implements ValidationRule
{
    use ValidatesAttributes;

    const FILE_ALLOWED_EXTENSION = ['csv', 'doc', 'docx', 'mp3', 'pdf', 'ppt', 'pptx', 'rtf', 'svg', 'txt', 'wav'];

    const IMAGE_ALLOWED_EXTENSIONS = ['gif', 'jfif', 'jif', 'jpeg', 'jpg', 'pdf', 'png', 'psd', 'tif', 'tiff', 'webp', 'bmp'];

    const VIDEO_ALLOWED_EXTENSIONS = ['mp4', 'webm', 'mkv'];

    protected FileMimeExtensionMatch $fileExtensionMatchRule;

    public function __construct(
        protected bool $isImage = false,
        protected array $allowedMimes = [],
        protected array $allowedExtensions = [],
        protected bool $isMultiple = false,
        protected ?int $maxKilobytes = null,
        protected ?int $minFiles = null,
        protected ?int $maxFiles = null,
        protected ?int $maxTotalKilobytes = null,
        protected array $allowedPathPrefixes = [],
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
        if ($this->isMultiple && is_string($value) && str_contains($value, ',')) {
            $value = array_filter(explode(',', $value), 'trim');
        }

        $values = is_array($value)
            ? array_values(array_filter($value, fn (mixed $item): bool => $item !== null && $item !== ''))
            : ($value === null || $value === '' ? [] : [$value]);
        $fileCount = count($values);

        if (! $this->isMultiple && $fileCount > 1) {
            $fail('validation.max.array')->translate(['max' => 1]);

            return;
        }

        if ($this->minFiles !== null && $fileCount > 0 && $fileCount < $this->minFiles) {
            $fail('validation.min.array')->translate(['min' => $this->minFiles]);

            return;
        }

        if ($this->maxFiles !== null && $fileCount > $this->maxFiles) {
            $fail('validation.max.array')->translate(['max' => $this->maxFiles]);

            return;
        }

        $totalBytes = 0;

        foreach ($values as $fileOrPath) {
            $size = $this->getSize($fileOrPath);

            if ($size !== null) {
                $totalBytes += $size;

                if ($this->maxKilobytes !== null && $size > $this->maxKilobytes * 1024) {
                    $fail('validation.max.file')->translate(['max' => $this->maxKilobytes]);

                    return;
                }
            }

            $this->validateFileOrPath($attribute, $fileOrPath, $fail);
        }

        if ($this->maxTotalKilobytes !== null && $totalBytes > $this->maxTotalKilobytes * 1024) {
            $fail('validation.max.file')->translate(['max' => $this->maxTotalKilobytes]);
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

        if (! is_string($value)) {
            return;
        }

        if (! Storage::exists($value)) {
            $fail('core::validation.file-not-exists')->translate(['value' => $value]);

            return;
        }

        if (! $this->hasAllowedPathPrefix($value) || ! $this->validateStoredFileType($value)) {
            $fail('validation.extensions')->translate(['values' => implode(', ', $this->allowedExtensions)]);
        }
    }

    protected function getSize(mixed $value): ?int
    {
        if ($this->isValidFileInstance($value)) {
            return $value->getSize();
        }

        if (is_string($value) && Storage::exists($value)) {
            try {
                return Storage::size($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function hasAllowedPathPrefix(string $path): bool
    {
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '\\')) {
            return false;
        }

        foreach ($this->allowedPathPrefixes as $prefix) {
            if (str_starts_with($path, rtrim($prefix, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    protected function validateStoredFileType(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, $this->allowedExtensions, true)) {
            return false;
        }

        try {
            $mimeType = Storage::mimeType($path);
        } catch (\Throwable) {
            return false;
        }

        $expectedMimeTypes = MimeTypes::getDefault()->getMimeTypes($extension);

        return is_string($mimeType)
            && ! empty($expectedMimeTypes)
            && in_array($mimeType, $expectedMimeTypes, true);
    }

    protected function validateMimeAndExtension(string $attribute, UploadedFile|File $value, Closure $fail): bool
    {
        $extension = $value instanceof UploadedFile ? $value->getClientOriginalExtension() : $value->getExtension();

        if ($this->allowedExtensions && ! in_array(strtolower($extension), $this->allowedExtensions, true)) {
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
