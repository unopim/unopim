<?php

namespace Webkul\CalculatedAttribute\Expression;

use Illuminate\Support\Carbon;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Webkul\CalculatedAttribute\Contracts\CategoryPathProvider;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;

/**
 * The complete set of functions a formula may call.
 *
 * Nothing outside this list is callable: {@see SafeExpressionLanguage} starts
 * from an empty function table, so a name that is not registered here fails at
 * parse time.
 *
 * Every evaluator receives the evaluation context as its first argument (that
 * is how Symfony's FunctionNode calls them). The category functions use it to
 * read the internal `__categories` / `__locale` keys that
 * {@see CalculatedValueResolver} binds, so
 * they need no hidden state of their own.
 *
 * Compilation to PHP source is never used — values are always evaluated — so the
 * compiler callbacks deliberately throw instead of emitting code.
 */
class FormulaFunctions implements ExpressionFunctionProviderInterface
{
    /**
     * Context key holding the product's category codes.
     */
    public const CATEGORIES_KEY = '__categories';

    /**
     * Context key holding the locale the formula is being evaluated for.
     */
    public const LOCALE_KEY = '__locale';

    /**
     * Prefix reserved for context keys the resolver injects. Formulas may not
     * use identifiers starting with it.
     */
    public const INTERNAL_PREFIX = '__';

    /**
     * Ceiling on the length of every string a function produces or consumes.
     *
     * A calculated attribute ends up in a grid cell, an export column or a JSON
     * field, so nothing longer than this is useful. More importantly, several
     * functions multiply their input (`replace`, `concat`/`join` with a long
     * separator, `number_format` decimals, `date_format` format characters), so
     * without a ceiling a short nested formula asks PHP for gigabytes and kills
     * the worker with a fatal error no `catch` can contain. Inputs are cut to
     * this length before use, amplifying functions build their result
     * incrementally and stop at it, and every string result is truncated to it.
     */
    public const MAX_LENGTH = 10000;

    /**
     * Ceiling on the decimals argument of `number_format`. A float carries
     * about 17 significant digits, so anything beyond this is padding.
     */
    public const MAX_DECIMALS = 20;

    public function __construct(
        protected CategoryPathProvider $categories,
    ) {}

    /**
     * @return array<int, ExpressionFunction>
     */
    public function getFunctions(): array
    {
        return array_merge(
            $this->stringFunctions(),
            $this->logicFunctions(),
            $this->numberFunctions(),
            $this->dateFunctions(),
            $this->categoryFunctions(),
        );
    }

    /**
     * The names of every callable function, for validation messages and the UI
     * help panel.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_map(
            fn (ExpressionFunction $function): string => $function->getName(),
            $this->getFunctions()
        );
    }

    /**
     * String handling.
     *
     * `concat(separator, ...parts)` is the workhorse: empty and null parts are
     * dropped rather than joined, so a product missing its second component
     * yields "ABC" and not "ABC-".
     *
     * @return array<int, ExpressionFunction>
     */
    protected function stringFunctions(): array
    {
        return [
            $this->fn('concat', fn (array $context, mixed $separator, mixed ...$parts): string => self::implodeBounded(
                self::text($separator),
                self::stringifyPresent($parts)
            )),

            $this->fn('join', function (array $context, mixed $separator, mixed $list): string {
                $items = is_array($list) ? $list : (self::isBlank($list) ? [] : [$list]);

                return self::implodeBounded(self::text($separator), self::stringifyPresent($items));
            }),

            $this->fn('coalesce', function (array $context, mixed ...$values): mixed {
                foreach ($values as $value) {
                    if (! self::isBlank($value)) {
                        return $value;
                    }
                }

                return null;
            }),

            $this->fn('upper', fn (array $context, mixed $value): string => mb_strtoupper(self::toString($value))),
            $this->fn('lower', fn (array $context, mixed $value): string => mb_strtolower(self::toString($value))),
            $this->fn('trim', fn (array $context, mixed $value): string => trim(self::toString($value))),
            $this->fn('length', fn (array $context, mixed $value): int => mb_strlen(self::toString($value))),

            $this->fn('substr', fn (array $context, mixed $value, mixed $start, mixed $length = null): string => mb_substr(
                self::toString($value),
                (int) $start,
                $length === null ? null : (int) $length
            )),

            $this->fn('replace', function (array $context, mixed $value, mixed $search, mixed $replace): string {
                $haystack = self::text($value);
                $needle = self::text($search);

                if ($needle === '') {
                    return $haystack;
                }

                return self::implodeBounded(self::text($replace), explode($needle, $haystack));
            }),

            $this->fn('pad_left', fn (array $context, mixed $value, mixed $length, mixed $pad = ' '): string => self::pad(
                $value,
                $length,
                $pad,
                STR_PAD_LEFT
            )),

            $this->fn('pad_right', fn (array $context, mixed $value, mixed $length, mixed $pad = ' '): string => self::pad(
                $value,
                $length,
                $pad,
                STR_PAD_RIGHT
            )),

            $this->fn('split', function (array $context, mixed $value, mixed $separator): array {
                $separator = self::text($separator);

                if ($separator === '') {
                    return [self::text($value)];
                }

                return explode($separator, self::text($value));
            }),
        ];
    }

    /**
     * Conditionals.
     *
     * A named `if()` exists alongside the ternary because the ternary reads
     * poorly once nested. Both branches are evaluated before the call, which is
     * harmless here: no function in this provider has side effects.
     *
     * @return array<int, ExpressionFunction>
     */
    protected function logicFunctions(): array
    {
        return [
            $this->fn('if', fn (array $context, mixed $condition, mixed $then, mixed $otherwise = null): mixed => $condition ? $then : $otherwise),

            $this->fn('is_empty', fn (array $context, mixed $value): bool => self::isBlank($value)),

            $this->fn('default', fn (array $context, mixed $value, mixed $fallback): mixed => self::isBlank($value) ? $fallback : $value),
        ];
    }

    /**
     * @return array<int, ExpressionFunction>
     */
    protected function numberFunctions(): array
    {
        return [
            $this->fn('round', fn (array $context, mixed $value, mixed $precision = 0): float => round((float) $value, (int) $precision)),
            $this->fn('floor', fn (array $context, mixed $value): float => floor((float) $value)),
            $this->fn('ceil', fn (array $context, mixed $value): float => ceil((float) $value)),
            $this->fn('abs', fn (array $context, mixed $value): float => abs((float) $value)),

            $this->fn('min', fn (array $context, mixed ...$values): mixed => $values === [] ? null : min(...$values)),
            $this->fn('max', fn (array $context, mixed ...$values): mixed => $values === [] ? null : max(...$values)),

            $this->fn('number_format', fn (array $context, mixed $value, mixed $decimals = 2, mixed $decimalPoint = '.', mixed $thousandsSeparator = ''): string => number_format(
                (float) $value,
                min(max((int) $decimals, 0), self::MAX_DECIMALS),
                self::text($decimalPoint),
                self::text($thousandsSeparator)
            )),
        ];
    }

    /**
     * Dates.
     *
     * `date_add` takes Carbon's relative-time strings ("+1 month"), the same
     * vocabulary the rest of the application already uses.
     *
     * @return array<int, ExpressionFunction>
     */
    protected function dateFunctions(): array
    {
        return [
            $this->fn('now', fn (array $context): string => Carbon::now()->toDateTimeString()),

            $this->fn('date_format', fn (array $context, mixed $value, mixed $format): ?string => self::toDate($value)?->format(self::text($format))),

            $this->fn('date_add', function (array $context, mixed $value, mixed $modifier): ?string {
                $date = self::toDate($value);

                if (! $date instanceof Carbon) {
                    return null;
                }

                try {
                    return $date->copy()->modify(self::toString($modifier))->toDateTimeString();
                } catch (\Throwable) {
                    return null;
                }
            }),

            $this->fn('year', fn (array $context, mixed $value): ?int => self::toDate($value)?->year),
            $this->fn('month', fn (array $context, mixed $value): ?int => self::toDate($value)?->month),
            $this->fn('day', fn (array $context, mixed $value): ?int => self::toDate($value)?->day),
        ];
    }

    /**
     * @return array<int, ExpressionFunction>
     */
    protected function categoryFunctions(): array
    {
        return [
            $this->fn('category_path', fn (array $context, mixed $separator = '/'): string => self::implodeBounded(
                self::text($separator),
                $this->categories->deepestPath(self::contextCategories($context))
            )),

            $this->fn('category_label_path', fn (array $context, mixed $separator = '/'): string => self::implodeBounded(
                self::text($separator),
                $this->categories->deepestPath(
                    self::contextCategories($context),
                    self::contextLocale($context)
                )
            )),

            $this->fn('category_codes', fn (array $context): array => self::contextCategories($context)),

            $this->fn('category_labels', fn (array $context): array => $this->categories->labels(
                self::contextCategories($context),
                self::contextLocale($context)
            )),
        ];
    }

    /**
     * Build an ExpressionFunction whose compiler is disabled and whose string
     * result never exceeds {@see MAX_LENGTH}, so no function can hand the next
     * one an input larger than the ceiling.
     */
    protected function fn(string $name, callable $evaluator): ExpressionFunction
    {
        return new ExpressionFunction(
            $name,
            static function () use ($name): never {
                throw new \LogicException(sprintf('Calculated attribute formulas are evaluated, never compiled; "%s" has no compiler.', $name));
            },
            static function (mixed ...$arguments) use ($evaluator): mixed {
                $result = $evaluator(...$arguments);

                return is_string($result) ? mb_substr($result, 0, self::MAX_LENGTH) : $result;
            }
        );
    }

    /**
     * Join pieces like implode(), but stop as soon as the result reaches
     * {@see MAX_LENGTH} instead of allocating the whole product of glue and
     * piece count first.
     *
     * @param  array<int, string>  $pieces
     */
    protected static function implodeBounded(string $glue, array $pieces): string
    {
        $result = '';

        foreach (array_values($pieces) as $index => $piece) {
            $result .= ($index === 0 ? '' : $glue).mb_substr($piece, 0, self::MAX_LENGTH);

            if (strlen($result) >= self::MAX_LENGTH) {
                return mb_substr($result, 0, self::MAX_LENGTH);
            }
        }

        return $result;
    }

    /**
     * Render a value as a string no longer than {@see MAX_LENGTH}.
     */
    protected static function text(mixed $value): string
    {
        return mb_substr(self::toString($value), 0, self::MAX_LENGTH);
    }

    /**
     * Drop blank entries and render the rest as strings.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected static function stringifyPresent(array $values): array
    {
        $present = array_filter($values, static fn (mixed $value): bool => ! self::isBlank($value));

        return array_map(static fn (mixed $value): string => self::text($value), array_values($present));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    protected static function contextCategories(array $context): array
    {
        $categories = $context[self::CATEGORIES_KEY] ?? [];

        return is_array($categories) ? array_values(array_filter($categories, 'is_string')) : [];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected static function contextLocale(array $context): ?string
    {
        $locale = $context[self::LOCALE_KEY] ?? null;

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    /**
     * Pad to at most {@see MAX_LENGTH} characters.
     *
     * Without the clamp, `pad_left(sku, 2000000000)` asks str_pad() for a two
     * gigabyte string and takes the process down with it — from a text field
     * any catalogue manager can edit.
     */
    protected static function pad(mixed $value, mixed $length, mixed $pad, int $type): string
    {
        $pad = self::toString($pad);

        return str_pad(
            self::text($value),
            min(max((int) $length, 0), self::MAX_LENGTH),
            $pad === '' ? ' ' : $pad,
            $type
        );
    }

    /**
     * Treats null, the empty string and the empty array as "no value". Zero and
     * "0" are values, so a formula can concatenate a legitimate numeric 0.
     */
    public static function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    /**
     * Render any supported value as a string. Arrays (multiselect, gallery) are
     * comma-joined, which matches how the exporter already flattens them.
     */
    public static function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return implode(',', array_map(static fn (mixed $item): string => self::toString($item), $value));
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    protected static function toDate(mixed $value): ?Carbon
    {
        if (self::isBlank($value) || ! is_scalar($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
