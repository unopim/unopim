<?php

namespace Webkul\AiAgent\Services;

/**
 * Extracts a JSON object from a free-text LLM translation response.
 *
 * The previous inline extractor relied on the regex `\{[^{}]*\}`, which
 * cannot match objects containing nested objects and silently dropped whole
 * translations. This parser performs a string-aware, balanced-brace scan so
 * nested structures are captured, and can distinguish a genuinely absent
 * object from a response that was truncated mid-generation (e.g. by a
 * max-token limit) — the latter must be reported as a failure rather than
 * being treated as a successful empty result.
 */
class TranslationResponseParser
{
    /**
     * Extract the first complete JSON object from an LLM response.
     *
     * Tries, in order: a direct decode, a fenced ```json code block, then a
     * balanced-brace scan for the first top-level `{ ... }`.
     *
     * @return array<string, mixed>|null null when no complete object is present
     */
    public static function extractObject(string $response): ?array
    {
        $response = trim($response);

        if ($response === '') {
            return null;
        }

        $decoded = json_decode($response, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $response, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $candidate = self::firstBalancedObject($response);

        if ($candidate !== null) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Whether the response opens a JSON object that is never closed — the
     * signature of a truncated (max-token-limited) generation.
     */
    public static function looksTruncated(string $response): bool
    {
        return str_contains($response, '{')
            && self::firstBalancedObject($response) === null;
    }

    /**
     * Return the substring of the first balanced top-level `{ ... }` object,
     * ignoring braces that appear inside string literals, or null when no
     * balanced object exists.
     */
    protected static function firstBalancedObject(string $string): ?string
    {
        $start = strpos($string, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($string);

        for ($i = $start; $i < $length; $i++) {
            $char = $string[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($string, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }
}
