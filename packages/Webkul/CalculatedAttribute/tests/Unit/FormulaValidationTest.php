<?php

use Webkul\CalculatedAttribute\Exceptions\InvalidFormulaException;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;
use Webkul\CalculatedAttribute\Services\FormulaCompiler;
use Webkul\CalculatedAttribute\Services\FormulaValidator;
use Webkul\CalculatedAttribute\Tests\Support\FakeCategoryPaths;
use Webkul\CalculatedAttribute\Tests\Support\InMemoryAttributeRegistry;

/**
 * @param  array<string, array<string, mixed>>  $attributes
 */
function calcValidator(array $attributes): FormulaValidator
{
    $registry = new InMemoryAttributeRegistry($attributes);

    $language = new SafeExpressionLanguage(new FormulaFunctions(new FakeCategoryPaths));

    return new FormulaValidator(new FormulaCompiler($language, $registry), $registry);
}

it('accepts a formula that references existing attributes', function (): void {
    $validator = calcValidator([
        'manufacturer' => ['type' => 'text'],
        'mpn'          => ['type' => 'text'],
    ]);

    $compiled = $validator->validate('article_key', "concat('-', manufacturer, mpn)");

    expect($compiled->dependencies)->toEqualCanonicalizing(['manufacturer', 'mpn']);
});

it('rejects a direct circular dependency', function (): void {
    $validator = calcValidator([
        'a' => ['type' => 'calculated', 'formula' => "concat('-', b)"],
        'b' => ['type' => 'calculated', 'formula' => "concat('-', a)"],
    ]);

    expect(fn () => $validator->validate('a', "concat('-', b)"))
        ->toThrow(InvalidFormulaException::class, 'a -> b -> a');
});

it('rejects a transitive circular dependency', function (): void {
    $validator = calcValidator([
        'a' => ['type' => 'calculated', 'formula' => "concat('-', b)"],
        'b' => ['type' => 'calculated', 'formula' => "concat('-', c)"],
        'c' => ['type' => 'calculated', 'formula' => "concat('-', a)"],
    ]);

    expect(fn () => $validator->validate('a', "concat('-', b)"))
        ->toThrow(InvalidFormulaException::class, 'a -> b -> c -> a');
});

it('reports the cycle path on the exception', function (): void {
    $validator = calcValidator([
        'a' => ['type' => 'calculated', 'formula' => "concat('-', b)"],
        'b' => ['type' => 'calculated', 'formula' => "concat('-', a)"],
    ]);

    try {
        $validator->validate('a', "concat('-', b)");

        $this->fail('expected a circular dependency to be rejected');
    } catch (InvalidFormulaException $exception) {
        expect($exception->cycle())->toBe(['a', 'b', 'a']);
    }
});

it('rejects a self reference', function (): void {
    $validator = calcValidator(['a' => ['type' => 'calculated', 'formula' => 'sku']]);

    expect(fn () => $validator->validate('a', "concat('-', a)"))
        ->toThrow(InvalidFormulaException::class, 'a -> a');
});

it('allows a diamond, which is not a cycle', function (): void {
    $validator = calcValidator([
        'base'  => ['type' => 'text'],
        'left'  => ['type' => 'calculated', 'formula' => 'base'],
        'right' => ['type' => 'calculated', 'formula' => 'base'],
    ]);

    $compiled = $validator->validate('top', "concat('-', left, right)");

    expect($compiled->dependencies)->toEqualCanonicalizing(['left', 'right']);
});

it('rejects an unknown attribute code', function (): void {
    $validator = calcValidator(['manufacturer' => ['type' => 'text']]);

    expect(fn () => $validator->validate('out', "concat('-', manufactrer)"))
        ->toThrow(InvalidFormulaException::class, 'Unknown attribute or variable "manufactrer"');
});

it('rejects a function outside the whitelist', function (): void {
    $validator = calcValidator([]);

    expect(fn () => $validator->validate('out', "shell_exec('id')"))
        ->toThrow(InvalidFormulaException::class, 'Function "shell_exec" is not allowed');
});

it('rejects the built-in constant() reader', function (): void {
    $validator = calcValidator([]);

    expect(fn () => $validator->validate('out', "constant('PHP_VERSION')"))
        ->toThrow(InvalidFormulaException::class, 'Function "constant" is not allowed');
});

it('rejects the regex matches operator', function (): void {
    $validator = calcValidator([]);

    expect(fn () => $validator->validate('out', "sku matches '/^a/'"))
        ->toThrow(InvalidFormulaException::class, 'The "matches" operator is not allowed');
});

it('rejects the range operator, which would build an unbounded array', function (): void {
    $validator = calcValidator([]);

    expect(fn () => $validator->validate('out', 'join(\',\', 1..100000000)'))
        ->toThrow(InvalidFormulaException::class, 'The ".." operator is not allowed');
});

it('rejects the unbounded ~ concatenation operator', function (): void {
    $validator = calcValidator(['sku' => ['type' => 'text']]);

    expect(fn () => $validator->validate('out', '(sku ~ sku) ~ (sku ~ sku)'))
        ->toThrow(InvalidFormulaException::class, 'The "~" operator is not allowed');
});

it('checks a densely connected ladder of formulas in linear time', function (): void {
    $attributes = ['a1' => ['type' => 'calculated', 'formula' => "concat('-', sku)"], 'sku' => ['type' => 'text']];

    for ($level = 2; $level <= 24; $level++) {
        $previous = implode(', ', array_map(fn (int $index): string => "a{$index}", range(1, $level - 1)));

        $attributes["a{$level}"] = ['type' => 'calculated', 'formula' => "concat('-', {$previous})"];
    }

    $validator = calcValidator($attributes);

    $started = microtime(true);

    $validator->validate('top', "concat('-', ".implode(', ', array_map(fn (int $index): string => "a{$index}", range(1, 24))).')');

    expect(microtime(true) - $started)->toBeLessThan(2.0);
});

it('still finds a cycle that runs through an already explored branch', function (): void {
    $validator = calcValidator([
        'a'   => ['type' => 'calculated', 'formula' => "concat('-', c, b)"],
        'b'   => ['type' => 'calculated', 'formula' => "concat('-', c, d)"],
        'c'   => ['type' => 'calculated', 'formula' => "concat('-', sku)"],
        'd'   => ['type' => 'calculated', 'formula' => "concat('-', c, top)"],
        'top' => ['type' => 'calculated', 'formula' => "concat('-', sku)"],
        'sku' => ['type' => 'text'],
    ]);

    expect(fn () => $validator->validate('top', "concat('-', c, a)"))
        ->toThrow(InvalidFormulaException::class, 'top -> a -> b -> d -> top');
});

it('names the calculated attributes a formula reads', function (): void {
    $validator = calcValidator([
        'manufacturer' => ['type' => 'text'],
        'uses_it'      => ['type' => 'calculated', 'formula' => "concat('-', manufacturer, sku)"],
        'mentions_it'  => ['type' => 'calculated', 'formula' => "'manufacturer'"],
    ]);

    expect($validator->dependents('manufacturer'))->toBe(['uses_it']);
});

it('reports no dependents for an attribute no formula reads', function (): void {
    $validator = calcValidator([
        'lonely'  => ['type' => 'text'],
        'uses_it' => ['type' => 'calculated', 'formula' => 'sku'],
    ]);

    expect($validator->dependents('lonely'))->toBe([]);
});

it('rejects a reserved internal variable name', function (): void {
    $validator = calcValidator([]);

    expect(fn () => $validator->validate('out', '__categories'))
        ->toThrow(InvalidFormulaException::class);
});

it('rejects an empty formula', function (): void {
    $validator = calcValidator([]);

    expect(fn () => $validator->validate('out', '   '))
        ->toThrow(InvalidFormulaException::class, 'needs a formula');
});

it('rejects a formula beyond the length limit', function (): void {
    $validator = calcValidator([]);

    $long = "'".str_repeat('x', FormulaCompiler::MAX_LENGTH + 10)."'";

    expect(fn () => $validator->validate('out', $long))
        ->toThrow(InvalidFormulaException::class, 'may not be longer than');
});

it('rejects a syntactically broken formula', function (): void {
    $validator = calcValidator(['manufacturer' => ['type' => 'text']]);

    expect(fn () => $validator->validate('out', "concat('-', manufacturer"))
        ->toThrow(InvalidFormulaException::class);
});

it('treats a referenced plain attribute as a leaf, not a graph edge', function (): void {
    $validator = calcValidator([
        'plain' => ['type' => 'text'],
        'out'   => ['type' => 'calculated', 'formula' => 'plain'],
    ]);

    expect($validator->validate('other', "concat('-', plain, out)")->dependencies)
        ->toEqualCanonicalizing(['plain', 'out']);
});

it('ignores an already-stored formula that no longer compiles', function (): void {
    $validator = calcValidator([
        'stale' => ['type' => 'calculated', 'formula' => 'attribute_that_was_deleted'],
        'ok'    => ['type' => 'text'],
    ]);

    expect($validator->validate('fresh', "concat('-', stale, ok)")->dependencies)
        ->toEqualCanonicalizing(['stale', 'ok']);
});
