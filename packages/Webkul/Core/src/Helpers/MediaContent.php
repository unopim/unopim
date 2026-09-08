<?php

declare(strict_types=1);

namespace Webkul\Core\Helpers;

/**
 * Decides how a stored media file may be handed back to a browser.
 *
 * Uploaded files live on the public disk and are linked from the admin's own
 * origin, so anything the browser will execute has to be either rejected at
 * upload time or served in a way that cannot reach that origin.
 */
class MediaContent
{
    /**
     * Content types safe to render inline, mapped from the file extension
     * rather than sniffed, so a mislabelled file cannot pick its own type.
     */
    public const INLINE_SAFE_TYPES = [
        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'svg'  => 'image/svg+xml',
        'wav'  => 'audio/wav',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
    ];

    /**
     * Markers whose presence means the PDF asks a viewer to run something.
     */
    private const string PDF_ACTIVE_CONTENT = '/\/(OpenAction|AA|JavaScript|JS|Launch|EmbeddedFile|RichMedia)\b/';

    /**
     * Cap on the bytes read when scanning for active content.
     */
    private const MAX_SCAN_BYTES = 8 * 1024 * 1024;

    public static function inlineType(?string $extension): ?string
    {
        return self::INLINE_SAFE_TYPES[strtolower((string) $extension)] ?? null;
    }

    public static function isInlineSafe(?string $extension): bool
    {
        return self::inlineType($extension) !== null;
    }

    /**
     * Headers that neutralise an inline response: no type sniffing, and a
     * policy that denies every subresource the document might reach for.
     *
     * @return array<string, string>
     */
    public static function responseHeaders(): array
    {
        return [
            'X-Content-Type-Options'  => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline'; object-src 'none'; frame-ancestors 'self'",
            'Cache-Control'           => 'private, no-store',
        ];
    }

    /**
     * Whether a PDF carries an action or embedded payload a viewer would run.
     *
     * Scans the raw bytes, so a PDF that hides its catalog inside a compressed
     * object stream is not detected here; the inline response's sandboxing is
     * what covers that case.
     */
    public static function pdfHasActiveContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $handle = @fopen($realPath, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $content = (string) @fread($handle, self::MAX_SCAN_BYTES);
        } finally {
            @fclose($handle);
        }

        return $content !== '' && preg_match(self::PDF_ACTIVE_CONTENT, $content) === 1;
    }
}
