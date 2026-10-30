<?php

use Illuminate\Support\Str;
use Webkul\AdminApi\ApiDataSource\Catalog\ProductDataSource;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;

/**
 * Reach normalizeProduct() directly. The endpoint itself is covered by the
 * AdminApi suite; what is under test here is only that the derived values make
 * it into the `values` block, for every scope the response carries.
 *
 * @param  array<string, mixed>  $product
 * @return array<string, mixed>
 */
function calcApiNormalize(array $product): array
{
    $dataSource = app(ProductDataSource::class);

    $method = (new ReflectionClass($dataSource))->getMethod('normalizeProduct');
    $method->setAccessible(true);

    return $method->invoke($dataSource, $product);
}

/**
 * @param  array<string, mixed>  $values
 */
function calcApiProduct(array $values): Product
{
    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(6)]);

    $sku = 'CALC-'.Str::random(6);

    $product = Product::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    // `values` is not mass assignable on Product, so set it explicitly.
    $values['common']['sku'] = $sku;
    $product->values = $values;
    $product->save();

    return $product;
}

it('includes calculated values in the api product payload', function (): void {
    $manufacturer = Attribute::factory()->create([
        'code'              => 'mfr_'.Str::random(6),
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $calculatedCode = 'calc_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => "concat('-', {$manufacturer->code}, sku)",
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $product = calcApiProduct(['common' => [$manufacturer->code => 'ACME']]);

    $payload = calcApiNormalize($product->fresh()->load('attribute_family')->toArray());

    expect($payload['values']['common'][$calculatedCode] ?? null)->toBe("ACME-{$product->sku}");
});

it('computes a locale-scoped calculated value for every locale in the response', function (): void {
    $calculatedCode = 'calcloc_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => "concat('@', sku, locale)",
        'value_per_locale'  => 1,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $product = calcApiProduct(['common' => []]);

    $payload = calcApiNormalize($product->fresh()->load('attribute_family')->toArray());

    $locales = Channel::with('locales')->get()
        ->flatMap(fn ($channel) => $channel->locales->pluck('code'))
        ->unique();

    expect($locales)->not->toBeEmpty();

    foreach ($locales as $locale) {
        expect($payload['values']['locale_specific'][$locale][$calculatedCode] ?? null)
            ->toBe("{$product->sku}@{$locale}");
    }
});

it('passes a null values block through untouched', function (): void {
    app(AttributeRegistry::class)->flush();

    /*
     * A product's `values` may legitimately be null and the API has always
     * carried that through. Coercing it to an array here would change the
     * payload shape for every catalogue that does not use the feature — which
     * is exactly what an earlier version of this code did.
     */
    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(6)]);

    $product = Product::create([
        'sku'                 => 'CALC-'.Str::random(6),
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    $row = $product->fresh()->load('attribute_family')->toArray();
    $row['values'] = null;

    expect(calcApiNormalize($row)['values'])->toBeNull();
});

it('does not disturb stored values already present in the payload', function (): void {
    $plain = Attribute::factory()->create([
        'code'              => 'plain_'.Str::random(6),
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    Attribute::factory()->create([
        'code'              => 'calc_'.Str::random(6),
        'type'              => 'calculated',
        'formula'           => 'upper(sku)',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $product = calcApiProduct(['common' => [$plain->code => 'untouched']]);

    $payload = calcApiNormalize($product->fresh()->load('attribute_family')->toArray());

    expect($payload['values']['common'][$plain->code])->toBe('untouched');
});
