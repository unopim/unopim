<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

use Illuminate\Support\Str;

class TemplateParser
{
    public const int MAX_EXPRESSIONS = 100;

    public const int MAX_FILTERS = 5;

    private const int CACHE_LIMIT = 500;

    private const int LABEL_LIMIT = 60;

    private const string EXPRESSION_PATTERN = '/\{\{(.*?)\}\}/su';

    private const string BODY_PATTERN = '/^\s*(?<code>\w+)(?:\.(?<currency>\w+))?\s*(?<filters>(?:\|\s*\w+\s*(?:\(\s*(?:\'[^\']*\'|"[^"]*")\s*\)\s*)?)*)$/u';

    private const string FILTER_PATTERN = '/\|\s*(?<name>\w+)\s*(?:\(\s*(?:\'(?<single>[^\']*)\'|"(?<double>[^"]*)")\s*\))?/u';

    private array $cache = [];

    /**
     * Parse the template source into text and expression segments, caching the result.
     */
    public function parse(string $source, bool $html = false): ParsedTemplate
    {
        $key = ($html ? 'html:' : 'text:').hash('xxh128', $source);

        if (! isset($this->cache[$key]) && count($this->cache) >= self::CACHE_LIMIT) {
            $this->cache = [];
        }

        return $this->cache[$key] ??= $this->compile($source, $html);
    }

    /**
     * Determine if the value is a string that contains a template expression.
     */
    public function containsTemplate(mixed $value): bool
    {
        return is_string($value) && str_contains($value, '{{');
    }

    private function compile(string $source, bool $html): ParsedTemplate
    {
        $matches = [];
        $offset = 0;

        while (count($matches) <= self::MAX_EXPRESSIONS && preg_match(self::EXPRESSION_PATTERN, $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $matches[] = $match;
            $offset = $match[0][1] + strlen($match[0][0]);
        }

        if (count($matches) > self::MAX_EXPRESSIONS) {
            return new ParsedTemplate([$source], [new TemplateMessage('too-many-expressions', ['max' => self::MAX_EXPRESSIONS])]);
        }

        $segments = [];
        $errors = [];
        $cursor = 0;

        foreach ($matches as [[$text, $offset], [$body]]) {
            $segments[] = substr($source, $cursor, $offset - $cursor);
            $cursor = $offset + strlen($text);

            $expression = $this->parseExpression($html ? $this->decodeHtml($body) : $body);

            if ($expression instanceof TemplateMessage) {
                $errors[] = $expression;
                $segments[] = $text;

                continue;
            }

            $segments[] = $expression;
        }

        $tail = substr($source, $cursor);

        if (str_contains($tail, '{{')) {
            $errors[] = new TemplateMessage('unclosed-expression');
        }

        $segments[] = $tail;

        return new ParsedTemplate(
            array_values(array_filter($segments, fn (string|Expression $segment): bool => $segment !== '')),
            $errors,
        );
    }

    private function parseExpression(string $body): Expression|TemplateMessage
    {
        $label = $this->label($body);

        if (! preg_match(self::BODY_PATTERN, $body, $parts, PREG_UNMATCHED_AS_NULL)) {
            return new TemplateMessage('invalid-syntax', ['expression' => $label]);
        }

        preg_match_all(self::FILTER_PATTERN, (string) $parts['filters'], $calls, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        if (count($calls) > self::MAX_FILTERS) {
            return new TemplateMessage('too-many-filters', ['expression' => $label, 'max' => self::MAX_FILTERS]);
        }

        return new Expression(
            label: $label,
            attributeCode: $parts['code'],
            currency: $parts['currency'],
            filters: array_map(
                fn (array $call): FilterCall => new FilterCall($call['name'], $call['single'] ?? $call['double']),
                $calls,
            ),
        );
    }

    private function decodeHtml(string $body): string
    {
        return str_replace("\u{00A0}", ' ', html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function label(string $body): string
    {
        return Str::of(strip_tags($body))
            ->remove(['{', '}'])
            ->squish()
            ->limit(self::LABEL_LIMIT)
            ->value();
    }
}
