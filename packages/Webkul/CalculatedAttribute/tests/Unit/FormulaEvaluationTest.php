<?php

use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;
use Webkul\CalculatedAttribute\Services\FormulaCompiler;
use Webkul\CalculatedAttribute\Services\FormulaValidator;
use Webkul\CalculatedAttribute\Support\ProductContext;
use Webkul\CalculatedAttribute\Tests\Support\FakeCategoryPaths;
use Webkul\CalculatedAttribute\Tests\Support\InMemoryAttributeRegistry;

/**
 * Build a resolver over an in-memory attribute set and category tree.
 *
 * @param  array<string, array<string, mixed>>  $attributes
 * @param  array<string, array<string, mixed>>  $categories
 * @return array{0: CalculatedValueResolver, 1: InMemoryAttributeRegistry}
 */
function calcResolver(array $attributes, array $categories = []): array
{
    $registry = new InMemoryAttributeRegistry($attributes);

    $language = new SafeExpressionLanguage(new FormulaFunctions(new FakeCategoryPaths($categories)));

    $compiler = new FormulaCompiler($language);

    $validator = new FormulaValidator($compiler, $registry);

    return [new CalculatedValueResolver($language, $compiler, $validator, $registry), $registry];
}

/**
 * The "Artikelfamilie" shape: Arbeitskleidung -> Anzug, localised.
 */
function calcCategoryTree(): array
{
    return [
        'arbeitskleidung' => ['parent' => null, 'names' => ['de_DE' => 'Arbeitskleidung']],
        'anzug'           => ['parent' => 'arbeitskleidung', 'names' => ['de_DE' => 'Anzug']],
    ];
}

it('concatenates two attributes with the configured separator', function (): void {
    [$resolver] = calcResolver([
        'manufacturer' => ['type' => 'text'],
        'mpn'          => ['type' => 'text'],
        'article_key'  => ['type' => 'calculated', 'formula' => "concat('-', manufacturer, mpn)"],
    ]);

    $product = new ProductContext(values: ['common' => ['manufacturer' => 'ACME', 'mpn' => 'X-1']]);

    expect($resolver->resolveOne('article_key', $product, 'default', 'de_DE'))->toBe('ACME-X-1');
});

it('drops empty parts instead of leaving a dangling separator', function (): void {
    [$resolver] = calcResolver([
        'manufacturer' => ['type' => 'text'],
        'mpn'          => ['type' => 'text'],
        'article_key'  => ['type' => 'calculated', 'formula' => "concat('-', manufacturer, mpn)"],
    ]);

    $missing = new ProductContext(values: ['common' => ['manufacturer' => 'ACME']]);
    $blank = new ProductContext(values: ['common' => ['manufacturer' => 'ACME', 'mpn' => '']]);
    $whitespace = new ProductContext(values: ['common' => ['manufacturer' => 'ACME', 'mpn' => '   ']]);

    expect($resolver->resolveOne('article_key', $missing, 'default', 'de_DE'))->toBe('ACME');
    expect($resolver->resolveOne('article_key', $blank, 'default', 'de_DE'))->toBe('ACME');
    expect($resolver->resolveOne('article_key', $whitespace, 'default', 'de_DE'))->toBe('ACME');
});

it('returns an empty string when every source value is missing', function (): void {
    [$resolver] = calcResolver([
        'manufacturer' => ['type' => 'text'],
        'mpn'          => ['type' => 'text'],
        'article_key'  => ['type' => 'calculated', 'formula' => "concat('-', manufacturer, mpn)"],
    ]);

    expect($resolver->resolveOne('article_key', new ProductContext, 'default', 'de_DE'))->toBe('');
});

it('keeps a literal zero, which is a value and not an absence', function (): void {
    [$resolver] = calcResolver([
        'stock' => ['type' => 'text'],
        'label' => ['type' => 'calculated', 'formula' => "concat('/', 'qty', stock)"],
    ]);

    $product = new ProductContext(values: ['common' => ['stock' => '0']]);

    expect($resolver->resolveOne('label', $product, 'default', 'de_DE'))->toBe('qty/0');
});

it('builds the category label path for the Artikelfamilie case', function (): void {
    [$resolver] = calcResolver([
        'artikelfamilie' => ['type' => 'calculated', 'formula' => "category_label_path('#')", 'per_locale' => true],
    ], calcCategoryTree());

    $product = new ProductContext(categories: ['anzug', 'arbeitskleidung']);

    expect($resolver->resolveOne('artikelfamilie', $product, 'default', 'de_DE'))
        ->toBe('Arbeitskleidung#Anzug');
});

it('builds the category code path when labels are not asked for', function (): void {
    [$resolver] = calcResolver([
        'path' => ['type' => 'calculated', 'formula' => "category_path('#')"],
    ], calcCategoryTree());

    $product = new ProductContext(categories: ['anzug']);

    expect($resolver->resolveOne('path', $product, 'default', 'de_DE'))
        ->toBe('arbeitskleidung#anzug');
});

it('yields an empty path for a product with no categories', function (): void {
    [$resolver] = calcResolver([
        'artikelfamilie' => ['type' => 'calculated', 'formula' => "category_label_path('#')"],
    ], calcCategoryTree());

    expect($resolver->resolveOne('artikelfamilie', new ProductContext, 'default', 'de_DE'))->toBe('');
});

it('falls back to the category code when the locale has no name', function (): void {
    [$resolver] = calcResolver([
        'artikelfamilie' => ['type' => 'calculated', 'formula' => "category_label_path('#')"],
    ], calcCategoryTree());

    $product = new ProductContext(categories: ['anzug']);

    expect($resolver->resolveOne('artikelfamilie', $product, 'default', 'fr_FR'))
        ->toBe('arbeitskleidung#anzug');
});

it('reads each referenced attribute from its own scope', function (): void {
    [$resolver] = calcResolver([
        'plain'     => ['type' => 'text'],
        'localised' => ['type' => 'text', 'per_locale' => true],
        'channeled' => ['type' => 'text', 'per_channel' => true],
        'both'      => ['type' => 'text', 'per_locale' => true, 'per_channel' => true],
        'combined'  => ['type' => 'calculated', 'formula' => "concat('|', plain, localised, channeled, both)"],
    ]);

    $product = new ProductContext(values: [
        'common'                  => ['plain' => 'C'],
        'locale_specific'         => ['de_DE' => ['localised' => 'L']],
        'channel_specific'        => ['default' => ['channeled' => 'K']],
        'channel_locale_specific' => ['default' => ['de_DE' => ['both' => 'B']]],
    ]);

    expect($resolver->resolveOne('combined', $product, 'default', 'de_DE'))->toBe('C|L|K|B');
});

it('evaluates the same formula differently per locale', function (): void {
    [$resolver] = calcResolver([
        'name'  => ['type' => 'text', 'per_locale' => true],
        'label' => ['type' => 'calculated', 'formula' => 'name', 'per_locale' => true],
    ]);

    $product = new ProductContext(values: [
        'locale_specific' => [
            'de_DE' => ['name' => 'Anzug'],
            'en_US' => ['name' => 'Suit'],
        ],
    ]);

    expect($resolver->resolveOne('label', $product, 'default', 'de_DE'))->toBe('Anzug');
    expect($resolver->resolveOne('label', $product, 'default', 'en_US'))->toBe('Suit');
});

it('exposes the system variables', function (): void {
    [$resolver] = calcResolver([
        'ident' => ['type' => 'calculated', 'formula' => "concat('/', sku, family, channel, locale)"],
    ]);

    $product = new ProductContext(sku: 'SKU-1', family: 'default');

    expect($resolver->resolveOne('ident', $product, 'web', 'de_DE'))->toBe('SKU-1/default/web/de_DE');
});

it('supports fallbacks, conditionals and arithmetic', function (): void {
    [$resolver, $registry] = calcResolver([
        'short' => ['type' => 'text'],
        'long'  => ['type' => 'text'],
        'price' => ['type' => 'text'],
        'out'   => ['type' => 'calculated', 'formula' => 'coalesce(short, long)'],
    ]);

    $product = new ProductContext(values: ['common' => ['long' => 'fallback', 'price' => '10']]);

    expect($resolver->resolveOne('out', $product, 'default', 'de_DE'))->toBe('fallback');

    $registry->define('out', ['type' => 'calculated', 'formula' => "if(is_empty(short), 'none', short)"]);
    expect($resolver->resolveOne('out', $product, 'default', 'de_DE'))->toBe('none');

    $registry->define('out', ['type' => 'calculated', 'formula' => 'round(price * 1.19, 2)']);
    expect($resolver->resolveOne('out', $product, 'default', 'de_DE'))->toBe(11.9);
});

it('formats dates', function (): void {
    [$resolver] = calcResolver([
        'released' => ['type' => 'date'],
        'period'   => ['type' => 'calculated', 'formula' => "date_format(released, 'Y-m')"],
    ]);

    $product = new ProductContext(values: ['common' => ['released' => '2026-03-07']]);

    expect($resolver->resolveOne('period', $product, 'default', 'de_DE'))->toBe('2026-03');
});

it('returns an empty value rather than failing on an unparseable date', function (): void {
    [$resolver] = calcResolver([
        'released' => ['type' => 'date'],
        'period'   => ['type' => 'calculated', 'formula' => "date_format(released, 'Y-m')"],
    ]);

    $product = new ProductContext(values: ['common' => ['released' => 'not a date']]);

    expect($resolver->resolveOne('period', $product, 'default', 'de_DE'))->toBeNull();
});

it('resolves a calculated attribute that builds on another one', function (): void {
    [$resolver] = calcResolver([
        'manufacturer' => ['type' => 'text'],
        'mpn'          => ['type' => 'text'],
        'article_key'  => ['type' => 'calculated', 'formula' => "concat('-', manufacturer, mpn)"],
        'export_key'   => ['type' => 'calculated', 'formula' => "upper(concat('/', 'DE', article_key))"],
    ]);

    $product = new ProductContext(values: ['common' => ['manufacturer' => 'acme', 'mpn' => 'x-1']]);

    expect($resolver->resolveOne('export_key', $product, 'default', 'de_DE'))->toBe('DE/ACME-X-1');
});

it('flattens an array value the way the exporter does', function (): void {
    [$resolver] = calcResolver([
        'tags' => ['type' => 'multiselect'],
        'flat' => ['type' => 'calculated', 'formula' => "join('+', tags)"],
    ]);

    $product = new ProductContext(values: ['common' => ['tags' => ['red', 'blue']]]);

    expect($resolver->resolveOne('flat', $product, 'default', 'de_DE'))->toBe('red+blue');
});

it('returns null for an attribute that is not calculated', function (): void {
    [$resolver] = calcResolver(['plain' => ['type' => 'text']]);

    $product = new ProductContext(values: ['common' => ['plain' => 'value']]);

    expect($resolver->resolveOne('plain', $product, 'default', 'de_DE'))->toBeNull();
});

it('degrades to an empty value when a stored formula no longer compiles', function (): void {
    [$resolver] = calcResolver([
        'broken' => ['type' => 'calculated', 'formula' => 'deleted_attribute_code'],
    ]);

    expect($resolver->resolveOne('broken', new ProductContext, 'default', 'de_DE'))->toBeNull();
});

it('merges computed values into the scope each attribute declares', function (): void {
    [$resolver] = calcResolver([
        'sku_upper' => ['type' => 'calculated', 'formula' => 'upper(sku)'],
        'localised' => ['type' => 'calculated', 'formula' => 'lower(sku)', 'per_locale' => true],
    ]);

    $product = new ProductContext(sku: 'Ab-1');

    $merged = $resolver->mergeInto(['common' => ['kept' => 'yes']], $product, 'default', 'de_DE');

    expect($merged['common']['kept'])->toBe('yes');
    expect($merged['common']['sku_upper'])->toBe('AB-1');
    expect($merged['locale_specific']['de_DE']['localised'])->toBe('ab-1');
});
