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

    return new FormulaValidator(new FormulaCompiler($language), $registry);
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

it('rejects a self reference as an unknown variable', function (): void {
    $validator = calcValidator(['a' => ['type' => 'calculated', 'formula' => 'sku']]);

    expect(fn () => $validator->validate('a', "concat('-', a)"))
        ->toThrow(InvalidFormulaException::class, 'Unknown attribute or variable "a"');
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
        ->toThrow(InvalidFormulaException::class, 'Function "matches" is not allowed');
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
