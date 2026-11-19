<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Support\ProductContext;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;
use Webkul\CalculatedAttribute\Services\CategoryPathResolver;
use Webkul\Category\Models\Category;

/**
 * Under Octane and in a queue worker the process outlives the request, so a
 * cache that is only ever built once is a cache that serves yesterday's
 * catalogue. These assert both halves of the fix: the instances are discarded
 * between units of work, and a change inside one is picked up straight away.
 */
it('discards its caches between requests or jobs', function (): void {
    $registry = app(AttributeRegistry::class);
    $categories = app(CategoryPathResolver::class);
    $resolver = app(CalculatedValueResolver::class);

    app()->forgetScopedInstances();

    expect(app(AttributeRegistry::class))->not->toBe($registry)
        ->and(app(CategoryPathResolver::class))->not->toBe($categories)
        ->and(app(CalculatedValueResolver::class))->not->toBe($resolver);
});

it('picks up a changed formula without restarting the process', function (): void {
    $code = 'calc_'.Str::random(6);

    $attribute = Attribute::factory()->create([
        'code'              => $code,
        'type'              => 'calculated',
        'formula'           => 'upper(sku)',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $resolver = app(CalculatedValueResolver::class);
    $product = new ProductContext(sku: 'Ab-1');

    expect($resolver->resolveOne($code, $product, 'default', 'en_US'))->toBe('AB-1');

    $attribute->formula = 'lower(sku)';
    $attribute->save();

    Event::dispatch('catalog.attribute.update.after', $attribute);

    expect($resolver->resolveOne($code, $product, 'default', 'en_US'))->toBe('ab-1');
});

it('picks up a renamed category without restarting the process', function (): void {
    $locale = config('app.locale');

    $root = Category::whereIsRoot()->first() ?? Category::create(['code' => 'root_'.Str::random(6)]);

    $category = Category::create(['code' => 'cat_'.Str::random(6), 'parent_id' => $root->id]);
    $category->additional_data = ['locale_specific' => [$locale => ['name' => 'Before']]];
    $category->save();

    expect(app(CategoryPathResolver::class)->labels([$category->code], $locale))->toBe(['Before']);

    $category->additional_data = ['locale_specific' => [$locale => ['name' => 'After']]];
    $category->save();

    Event::dispatch('catalog.category.update.after', $category);

    expect(app(CategoryPathResolver::class)->labels([$category->code], $locale))->toBe(['After']);
});

it('reads a product\'s own categories and their ancestors, not the whole tree', function (): void {
    $locale = config('app.locale');

    $root = Category::whereIsRoot()->first() ?? Category::create(['code' => 'root_'.Str::random(6)]);

    $parent = Category::create(['code' => 'par_'.Str::random(6), 'parent_id' => $root->id]);
    $parent->additional_data = ['locale_specific' => [$locale => ['name' => 'Parent']]];
    $parent->save();

    $child = Category::create(['code' => 'chi_'.Str::random(6), 'parent_id' => $parent->id]);
    $child->additional_data = ['locale_specific' => [$locale => ['name' => 'Child']]];
    $child->save();

    foreach (range(1, 5) as $index) {
        Category::create(['code' => 'oth_'.Str::random(6), 'parent_id' => $root->id]);
    }

    $resolver = app(CategoryPathResolver::class);
    $resolver->flush();

    expect($resolver->deepestPath([$child->code], $locale))->toBe(['Parent', 'Child']);

    $loaded = (new ReflectionClass($resolver))->getProperty('byId');
    $loaded->setAccessible(true);

    /*
     * The child, its parent and the technical root — and none of the siblings,
     * which is the difference between this and reading the whole table.
     */
    expect($loaded->getValue($resolver))->toHaveCount(3);
});
