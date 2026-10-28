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
     */
    public function sanitize(mixed $css): string
    {
        if (! is_string($css)) {
            return '';
        }

        $css = preg_replace('#</?style\b[^>]*>#i', '', $css) ?? '';

        return trim(str_replace(['<', "\0"], ['\3c ', ''], $css));
    }
}
