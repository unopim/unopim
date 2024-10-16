<?php

namespace Webkul\Core\Traits;

use enshrined\svgSanitize\Sanitizer as MainSanitizer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;

trait Sanitizer
{
    /**
     * List of mime types which needs to check.
     */
    public $mimeTypes = [
        'image/svg',
        'image/svg+xml',
    ];

    /**
     * Sanitize SVG file.
     */
    public function sanitizeSVG(string $path, ?string $mimeType): void
    {
        if ($this->isFileSVG($mimeType)) {
            /* sanitizer instance */
            $sanitizer = new MainSanitizer;

            /* grab svg file */
            $dirtySVG = Storage::get($path);

            /* save sanitized svg */
            Storage::put($path, $sanitizer->sanitize($dirtySVG));
        }
    }

    /**
     * Check file mime type
     */
    public function isFileSVG(?string $mimeType): bool
    {
        return in_array($mimeType, $this->mimeTypes);
    }
}
