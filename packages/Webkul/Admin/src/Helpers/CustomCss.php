<?php

namespace Webkul\Admin\Helpers;

class CustomCss
{
    /**
     * Core config key holding the admin custom CSS.
     */
    public const CONFIG_CODE = 'general.content.custom_scripts.custom_css';

    /**
     * Maximum accepted length, sized so the sanitized value fits a text column.
     */
    public const MAX_LENGTH = 15000;

    /**
     * Matches @import and functions able to load a remote resource.
     */
    protected const EXTERNAL_PATTERN = '/@import\b|(?<![\w-])(?:url|src|image-set|-webkit-image-set|image)(?=\s*\()/i';

    /**
     * Get the stored custom CSS, sanitized for output inside a style element.
     */
    public function get(): string
    {
        return $this->sanitize(core()->getConfigData(self::CONFIG_CODE));
    }

    /**
     * Make a CSS string safe to print inside a style element.
     *
     * Legacy values were stored with their own style wrapper, so those tags are
     * dropped. Any remaining "<" is replaced with its CSS escape, which renders
     * the same inside CSS strings but can never open "</style" in HTML.
     * Constructs that fetch external resources (@import, url(), src(,
     * image-set() are renamed so the browser ignores them; CSS escapes that
     * spell those names are removed first so they cannot dodge the check.
     */
    public function sanitize(mixed $css): string
    {
        if (! is_string($css)) {
            return '';
        }

        $css = preg_replace('#</?style\b[^>]*>#i', '', $css) ?? '';

        $css = str_replace("\0", '', $css);

        if (preg_match(self::EXTERNAL_PATTERN, $this->decodeEscapes($css))) {
            $css = str_replace('\\', '', $css);
        }

        $css = preg_replace_callback(
            self::EXTERNAL_PATTERN,
            fn (array $m): string => str_starts_with($m[0], '@') ? '@blocked' : 'blocked',
            $css
        ) ?? '';

        return trim(str_replace('<', '\3c ', $css));
    }

    /**
     * Resolve CSS backslash escapes so obfuscated names can be detected.
     *
     * Line breaks are normalised first, as the CSS tokenizer does, so a CRLF
     * after a hex escape is consumed as one terminator instead of leaving a
     * stray LF that splits the decoded name.
     */
    protected function decodeEscapes(string $css): string
    {
        $css = str_replace(["\r\n", "\r", "\f"], "\n", $css);

        $css = preg_replace_callback(
            '/\\\\([0-9a-f]{1,6})\s?/i',
            fn (array $m): string => mb_chr((int) hexdec($m[1]), 'UTF-8') ?: '',
            $css
        ) ?? '';

        return preg_replace('/\\\\(.)/su', '$1', $css) ?? '';
    }
}
