<?php

namespace Webkul\Core\HtmlPurifier;

/**
 * The embed sources rich text may frame, shared by the server purifier and the editor.
 */
class RichTextEmbeds
{
    /**
     * Configured embed URL prefixes, keeping only https ones.
     *
     * @return array<int, string>
     */
    public static function prefixes(): array
    {
        return array_values(array_filter(
            (array) config('rich_text.embed_url_prefixes', []),
            fn (mixed $prefix): bool => is_string($prefix) && str_starts_with($prefix, 'https://'),
        ));
    }

    /**
     * Pattern an iframe source must match; with no prefix configured it matches nothing.
     */
    public static function sourcePattern(): string
    {
        $prefixes = self::prefixes();

        if ($prefixes === []) {
            return '%^(?!)%';
        }

        return '%^(?:'.implode('|', array_map(fn (string $prefix): string => preg_quote($prefix, '%'), $prefixes)).')%';
    }
}
