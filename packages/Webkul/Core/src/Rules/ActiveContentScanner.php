<?php

namespace Webkul\Core\Rules;

use ZipArchive;

/**
 * Detects embedded active content (scripts, macros, auto-executing OLE
 * objects) in uploaded document files.
 */
class ActiveContentScanner
{
    public static function scan(string $extension, string $path): ?string
    {
        return match (strtolower($extension)) {
            'pdf'          => self::scanPdf($path),
            'svg'          => self::scanSvg($path),
            'docx', 'pptx' => self::scanOoxml($path),
            'doc', 'ppt'   => self::scanLegacyOle($path),
            'rtf'          => self::scanRtf($path),
            default        => null,
        };
    }

    protected static function scanPdf(string $path): ?string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $patterns = ['/\/JavaScript\b/', '/\/JS\b/', '/\/OpenAction\b/', '/\/AA\b/', '/\/Launch\b/', '/\/RichMedia\b/'];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $contents)) {
                return 'embedded_javascript_or_action';
            }
        }

        return null;
    }

    protected static function scanSvg(string $path): ?string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $previousState = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $loaded = $dom->loadXML($contents, LIBXML_NONET);
        libxml_use_internal_errors($previousState);

        if (! $loaded) {
            return 'invalid_svg_markup';
        }

        if ($dom->getElementsByTagName('script')->length > 0) {
            return 'embedded_script_tag';
        }

        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//@*') as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (str_starts_with($name, 'on')) {
                return 'inline_event_handler';
            }

            if (in_array($name, ['href', 'xlink:href'], true) && stripos(trim($attribute->nodeValue), 'javascript:') === 0) {
                return 'javascript_uri';
            }
        }

        return null;
    }

    protected static function scanOoxml(string $path): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return null;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name !== false && str_contains(strtolower($name), 'vbaproject.bin')) {
                $zip->close();

                return 'embedded_vba_macro';
            }
        }

        $zip->close();

        return null;
    }

    protected static function scanLegacyOle(string $path): ?string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        if (str_contains($contents, "V\x00B\x00A\x00")) {
            return 'embedded_vba_macro';
        }

        return null;
    }

    protected static function scanRtf(string $path): ?string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        if (preg_match('/\\\\obj(autlink|update)\b/i', $contents)) {
            return 'auto_executing_ole_object';
        }

        return null;
    }
}
