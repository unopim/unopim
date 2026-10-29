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
     * @return array<int, ExpressionFunction>
     */
    protected function stringFunctions(): array
    {
        return [
            /*
             * concat(separator, ...parts) — the workhorse. Empty and null parts
             * are dropped rather than joined, so a product missing its second
             * component yields "ABC" and not "ABC-".
             */
            $this->fn('concat', fn ($context, $separator, ...$parts): string => implode(
                self::toString($separator),
                self::stringifyPresent($parts)
            )),

            $this->fn('join', function ($context, $separator, $list): string {
                $items = is_array($list) ? $list : (self::isBlank($list) ? [] : [$list]);

                return implode(self::toString($separator), self::stringifyPresent($items));
            }),

            $this->fn('coalesce', function ($context, ...$values) {
                foreach ($values as $value) {
                    if (! self::isBlank($value)) {
                        return $value;
                    }
                }

                return null;
            }),

            $this->fn('upper', fn ($context, $value): string => mb_strtoupper(self::toString($value))),
            $this->fn('lower', fn ($context, $value): string => mb_strtolower(self::toString($value))),
            $this->fn('trim', fn ($context, $value): string => trim(self::toString($value))),
            $this->fn('length', fn ($context, $value): int => mb_strlen(self::toString($value))),

            $this->fn('substr', fn ($context, $value, $start, $length = null): string => mb_substr(
                self::toString($value),
                (int) $start,
                $length === null ? null : (int) $length
            )),

            $this->fn('replace', fn ($context, $value, $search, $replace): string => str_replace(
                self::toString($search),
                self::toString($replace),
                self::toString($value)
            )),

            $this->fn('pad_left', fn ($context, $value, $length, $pad = ' '): string => self::pad(
                $value,
                $length,
                $pad,
                STR_PAD_LEFT
            )),

            $this->fn('pad_right', fn ($context, $value, $length, $pad = ' '): string => self::pad(
                $value,
                $length,
                $pad,
                STR_PAD_RIGHT
            )),

            $this->fn('split', function ($context, $value, $separator): array {
                $separator = self::toString($separator);

                if ($separator === '') {
                    return [self::toString($value)];
                }

                return explode($separator, self::toString($value));
            }),
        ];
    }

    /**
     * @return array<int, ExpressionFunction>
     */
    protected function logicFunctions(): array
    {
        return [
            /*
             * Named if() rather than relying only on the ternary, because the
             * requirement asks for an explicit conditional and the ternary reads
             * poorly once nested. Both branches are evaluated before the call —
             * harmless here, as no function in this provider has side effects.
             */
            $this->fn('if', fn ($context, $condition, $then, $otherwise = null) => $condition ? $then : $otherwise),

            $this->fn('is_empty', fn ($context, $value): bool => self::isBlank($value)),

            $this->fn('default', fn ($context, $value, $fallback) => self::isBlank($value) ? $fallback : $value),
        ];
    }

    /**
     * @return array<int, ExpressionFunction>
     */
    protected function numberFunctions(): array
    {
        return [
            $this->fn('round', fn ($context, $value, $precision = 0): float => round((float) $value, (int) $precision)),
            $this->fn('floor', fn ($context, $value): float => floor((float) $value)),
            $this->fn('ceil', fn ($context, $value): float => ceil((float) $value)),
            $this->fn('abs', fn ($context, $value): float => abs((float) $value)),

            $this->fn('min', fn ($context, ...$values) => $values === [] ? null : min(...$values)),
            $this->fn('max', fn ($context, ...$values) => $values === [] ? null : max(...$values)),

            $this->fn('number_format', fn ($context, $value, $decimals = 2, $decimalPoint = '.', $thousandsSeparator = ''): string => number_format(
                (float) $value,
                (int) $decimals,
                self::toString($decimalPoint),
                self::toString($thousandsSeparator)
            )),
        ];
    }

    /**
     * @return array<int, ExpressionFunction>
     */
    protected function dateFunctions(): array
    {
        return [
            $this->fn('now', fn ($context): string => Carbon::now()->toDateTimeString()),

            $this->fn('date_format', fn ($context, $value, $format): ?string => self::toDate($value)?->format(self::toString($format))),

            /*
             * date_add uses Carbon's relative-time strings ("+1 month"), the same
             * vocabulary the rest of the application already uses.
             */
            $this->fn('date_add', function ($context, $value, $modifier): ?string {
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

            $this->fn('year', fn ($context, $value): ?int => self::toDate($value)?->year),
            $this->fn('month', fn ($context, $value): ?int => self::toDate($value)?->month),
            $this->fn('day', fn ($context, $value): ?int => self::toDate($value)?->day),
        ];
    }

    /**
     * @return array<int, ExpressionFunction>
     */
    protected function categoryFunctions(): array
    {
        return [
            $this->fn('category_path', fn ($context, $separator = '/'): string => implode(
                self::toString($separator),
                $this->categories->deepestPath(self::contextCategories($context))
            )),

            $this->fn('category_label_path', fn ($context, $separator = '/'): string => implode(
                self::toString($separator),
                $this->categories->deepestPath(
                    self::contextCategories($context),
                    self::contextLocale($context)
                )
            )),

            $this->fn('category_codes', fn ($context): array => self::contextCategories($context)),

            $this->fn('category_labels', fn ($context): array => $this->categories->labels(
                self::contextCategories($context),
                self::contextLocale($context)
            )),
        ];
    }

    /**
     * Build an ExpressionFunction whose compiler is disabled.
     */
    protected function fn(string $name, callable $evaluator): ExpressionFunction
    {
        return new ExpressionFunction(
            $name,
            static function () use ($name): never {
                throw new \LogicException(sprintf('Calculated attribute formulas are evaluated, never compiled; "%s" has no compiler.', $name));
            },
            $evaluator
        );
    }

    /**
     * Drop blank entries and render the rest as strings.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected static function stringifyPresent(array $values): array
    {
        $present = array_filter($values, static fn ($value): bool => ! self::isBlank($value));

        return array_map(static fn ($value): string => self::toString($value), array_values($present));
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

    protected static function pad(mixed $value, mixed $length, mixed $pad, int $type): string
    {
        $pad = self::toString($pad);

        return str_pad(self::toString($value), (int) $length, $pad === '' ? ' ' : $pad, $type);
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
            return implode(',', array_map(static fn ($item): string => self::toString($item), $value));
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
