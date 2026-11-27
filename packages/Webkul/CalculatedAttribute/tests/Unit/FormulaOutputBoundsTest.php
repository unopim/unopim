<?php

use Webkul\Attribute\Support\ProductContext;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;
use Webkul\CalculatedAttribute\Services\FormulaCompiler;
use Webkul\CalculatedAttribute\Tests\Support\FakeCategoryPaths;
use Webkul\CalculatedAttribute\Tests\Support\InMemoryAttributeRegistry;

/**
 * @param  array<string, array<string, mixed>>  $attributes
 * @param  array<string, array<string, mixed>>  $categories
 */
function boundsResolver(array $attributes, array $categories = []): array
{
    $registry = new InMemoryAttributeRegistry($attributes);

    $language = new SafeExpressionLanguage(new FormulaFunctions(new FakeCategoryPaths($categories)));

    return [new CalculatedValueResolver($language, new FormulaCompiler($language, $registry), $registry), $registry];
}

/**
 * Wrap an expression in itself `$depth` times, `{x}` marking where the
 * previous level goes.
 */
function boundsNest(string $seed, string $template, int $depth): string
{
    $formula = $seed;

    for ($level = 0; $level < $depth; $level++) {
        $formula = str_replace('{x}', $formula, $template);
    }

    return $formula;
}

/**
 * A ten-level category chain c0 -> c1 -> ... -> c9.
 *
 * @return array<string, array<string, mixed>>
 */
function boundsDeepCategoryTree(): array
{
    $tree = [];

    for ($level = 0; $level < 10; $level++) {
        $tree["c{$level}"] = ['parent' => $level === 0 ? null : 'c'.($level - 1), 'names' => []];
    }

    return $tree;
}

/**
 * Evaluate a formula and return its value together with the peak memory it
 * needed beyond what was already in use.
 *
 * @param  array<string, array<string, mixed>>  $categories
 * @return array{0: mixed, 1: int}
 */
function boundsEvaluate(string $formula, array $categories = [], array $productCategories = []): array
{
    [$resolver] = boundsResolver([
        'amplified' => ['type' => 'calculated', 'formula' => $formula],
    ], $categories);

    $before = memory_get_usage();
    memory_reset_peak_usage();

    $value = $resolver->resolveOne('amplified', new ProductContext(sku: 'A', categories: $productCategories), 'default', 'de_DE');

    return [$value, memory_get_peak_usage() - $before];
}

dataset('amplifying formulas', [
    'nested replace'         => [fn (): string => boundsNest("'a'", "replace({x}, 'a', 'aaaaaaaaaa')", 9)],
    'number_format decimals' => [fn (): string => 'number_format(1, 1000000000)'],
    'concat separator'       => [fn (): string => boundsNest("'aaaaaaaaaa'", "concat({x}, '1', '2', '3', '4', '5', '6', '7', '8', '9', '10')", 8)],
    'join over split'        => [fn (): string => boundsNest("'aaaaaaaaaa'", "join({x}, split('a,a,a,a,a,a,a,a,a,a', ','))", 8)],
    'date_format format'     => [fn (): string => boundsNest("'rrrrrrrrrr'", "date_format('2024-01-01', {x})", 15)],
]);

it('keeps an amplifying formula within the length and memory ceiling', function (Closure $formula): void {
    [$value, $memory] = boundsEvaluate($formula());

    expect(mb_strlen((string) $value))->toBeLessThanOrEqual(FormulaFunctions::MAX_LENGTH)
        ->and($memory)->toBeLessThan(32 * 1024 * 1024);
})->with('amplifying formulas');

it('keeps a nested category path separator within the ceiling', function (): void {
    [$value, $memory] = boundsEvaluate(
        boundsNest("'##########'", 'category_path({x})', 8),
        boundsDeepCategoryTree(),
        ['c9']
    );

    expect(mb_strlen((string) $value))->toBeLessThanOrEqual(FormulaFunctions::MAX_LENGTH)
        ->and($memory)->toBeLessThan(32 * 1024 * 1024);
});

it('keeps an array literal repeating a long value within the ceiling', function (string $wrapper): void {
    $formula = sprintf($wrapper, '['.implode(', ', array_fill(0, 300, 'body')).']');

    [$resolver] = boundsResolver([
        'body'      => ['type' => 'textarea'],
        'amplified' => ['type' => 'calculated', 'formula' => $formula],
    ]);

    $product = new ProductContext(values: ['common' => ['body' => str_repeat('x', 65536)]]);

    $before = memory_get_usage();
    memory_reset_peak_usage();

    $value = $resolver->resolveOne('amplified', $product, 'default', 'de_DE');

    expect(strtolower((string) $value))->toStartWith('xxxx')
        ->and(mb_strlen((string) $value))->toBeLessThanOrEqual(FormulaFunctions::MAX_LENGTH)
        ->and(memory_get_peak_usage() - $before)->toBeLessThan(32 * 1024 * 1024);
})->with([
    'returned as the value' => ['%s'],
    'passed to a function'  => ['upper(%s)'],
]);

it('still replaces normally below the ceiling', function (string $formula, string $expected): void {
    [$value] = boundsEvaluate($formula);

    expect($value)->toBe($expected);
})->with([
    'every occurrence'                    => ["replace('a-b-c', '-', '/')", 'a/b/c'],
    'longer replacement'                  => ["replace('a-b', '-', ' :: ')", 'a :: b'],
    'empty needle leaves the value alone' => ["replace('abc', '', 'x')", 'abc'],
    'no occurrence'                       => ["replace('abc', 'z', 'x')", 'abc'],
]);

it('still formats numbers normally below the ceiling', function (): void {
    [$value] = boundsEvaluate("number_format(1234.5, 2, ',', '.')");

    expect($value)->toBe('1.234,50');
});

it('caps number_format decimals', function (): void {
    [$value] = boundsEvaluate('number_format(1, 1000000000)');

    expect($value)->toBe('1.'.str_repeat('0', FormulaFunctions::MAX_DECIMALS));
});

it('never lets any registered function return a string beyond the ceiling', function (): void {
    $functions = (new FormulaFunctions(new FakeCategoryPaths(boundsDeepCategoryTree())))->getFunctions();

    $long = str_repeat('a,', FormulaFunctions::MAX_LENGTH);
    $context = [FormulaFunctions::CATEGORIES_KEY => ['c9'], FormulaFunctions::LOCALE_KEY => 'de_DE'];

    $argumentSets = [
        [$long, $long, $long, $long],
        [$long, 'a', $long, $long],
        [1, 2000000000, $long, $long],
        ['2024-01-01', str_repeat('r', FormulaFunctions::MAX_LENGTH), $long, $long],
    ];

    foreach ($functions as $function) {
        foreach ($argumentSets as $arguments) {
            try {
                $result = ($function->getEvaluator())($context, ...$arguments);
            } catch (Throwable) {
                continue;
            }

            if (is_string($result)) {
                expect(mb_strlen($result))->toBeLessThanOrEqual(FormulaFunctions::MAX_LENGTH, $function->getName());
            }
        }
    }
});
