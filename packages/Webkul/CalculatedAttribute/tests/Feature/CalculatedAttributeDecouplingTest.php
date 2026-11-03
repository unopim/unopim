<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\DerivedValueResolver;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Services\NullDerivedValueResolver;
use Webkul\Attribute\Support\ProductContext;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;
use Webkul\Product\Models\Product;

/**
 * Removing the provider line from bootstrap/providers.php has to remove the
 * feature, not break the installation. A booted test cannot unregister a
 * provider, so it is asserted in two halves: core names nothing from this
 * package, and core behaves correctly against the default no-op binding, which
 * is all it would be left with.
 */
it('keeps core packages free of any reference to this package', function (): void {
    $core = [
        'Admin',
        'AdminApi',
        'Attribute',
        'Category',
        'Completeness',
        'Core',
        'DataTransfer',
        'Product',
    ];

    $offenders = [];

    foreach ($core as $package) {
        $directory = base_path("packages/Webkul/{$package}/src");

        if (! is_dir($directory)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (str_contains((string) file_get_contents($file->getPathname()), 'Webkul\CalculatedAttribute')) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('registers the feature through the core contract rather than a core import', function (): void {
    expect(app(DerivedValueResolver::class))->toBeInstanceOf(CalculatedValueResolver::class);
});

it('leaves product values untouched when nothing derives them', function (): void {
    $this->app->bind(DerivedValueResolver::class, NullDerivedValueResolver::class);

    $resolver = app(DerivedValueResolver::class);

    $values = ['common' => ['name' => 'Example']];
    $product = new ProductContext(values: $values);

    expect($resolver->hasDerivedValues())->toBeFalse()
        ->and($resolver->resolve($product, 'default', 'en_US'))->toBe([])
        ->and($resolver->mergeInto($values, $product, 'default', 'en_US'))->toBe($values)
        ->and($resolver->mergeAllScopes($values, $product, ['default' => ['en_US']]))->toBe($values);
});

it('renders the product form against the default resolver', function (): void {
    $this->loginAsAdmin();

    $calculatedCode = 'calc_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => 'upper(sku)',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(6)]);

    $product = Product::create([
        'sku'                 => 'DEC-'.Str::random(6),
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    $this->app->bind(DerivedValueResolver::class, NullDerivedValueResolver::class);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    expect($content)->not->toContain(strtoupper($product->sku));
});
