<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\MagicAI\Jobs\SaveTranslatedDataJob;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\ProductValueMapper;

function translatableAttribute(): Attribute
{
    return Attribute::query()->where('value_per_locale', 1)->firstOrFail();
}

function productWithValue(Attribute $attribute, string $channel, string $locale, string $value): Product
{
    $product = Product::factory()->create();

    $product->values = resolve(ProductValueMapper::class)->setScopedValue(
        $product->values ?? [],
        $attribute,
        $channel,
        $locale,
        $value,
    );

    $product->save();

    return $product;
}

it('writes the translated value into the target locale scope', function () {
    $attribute = translatableAttribute();
    $product = productWithValue($attribute, 'default', 'en_US', 'Source text');

    (new SaveTranslatedDataJob(
        $product->id,
        [['locale' => 'fr_FR', 'content' => 'Texte traduit']],
        'default',
        $attribute->code,
    ))->handle(
        resolve(ProductRepository::class),
        resolve(AttributeRepository::class),
        resolve(ProductValueMapper::class),
    );

    expect(resolve(ProductValueMapper::class)->getScopedValue(
        $product->fresh()->values,
        $attribute,
        'default',
        'fr_FR',
    ))->toBe('Texte traduit');
});

it('leaves an already translated value alone unless the install opts into replacing', function () {
    $attribute = translatableAttribute();
    $product = productWithValue($attribute, 'default', 'fr_FR', 'Valeur existante');

    (new SaveTranslatedDataJob(
        $product->id,
        [['locale' => 'fr_FR', 'content' => 'Nouvelle valeur']],
        'default',
        $attribute->code,
    ))->handle(
        resolve(ProductRepository::class),
        resolve(AttributeRepository::class),
        resolve(ProductValueMapper::class),
    );

    expect(resolve(ProductValueMapper::class)->getScopedValue(
        $product->fresh()->values,
        $attribute,
        'default',
        'fr_FR',
    ))->toBe('Valeur existante');
});

it('overwrites an existing translation when the install opts into replacing', function () {
    DB::table('core_config')->insert([
        'code'       => 'general.magic_ai.translation.replace',
        'value'      => '1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attribute = translatableAttribute();
    $product = productWithValue($attribute, 'default', 'fr_FR', 'Valeur existante');

    (new SaveTranslatedDataJob(
        $product->id,
        [['locale' => 'fr_FR', 'content' => 'Nouvelle valeur']],
        'default',
        $attribute->code,
    ))->handle(
        resolve(ProductRepository::class),
        resolve(AttributeRepository::class),
        resolve(ProductValueMapper::class),
    );

    expect(resolve(ProductValueMapper::class)->getScopedValue(
        $product->fresh()->values,
        $attribute,
        'default',
        'fr_FR',
    ))->toBe('Nouvelle valeur');
});

it('does nothing when the field is not a known attribute', function () {
    $product = Product::factory()->create();
    $before = $product->values;

    (new SaveTranslatedDataJob($product->id, [['locale' => 'fr_FR', 'content' => 'x']], 'default', 'no_such_attribute'))
        ->handle(
            resolve(ProductRepository::class),
            resolve(AttributeRepository::class),
            resolve(ProductValueMapper::class),
        );

    expect($product->fresh()->values)->toEqual($before);
});

it('purifies a translated rich-text value before it is stored', function () {
    $attribute = Attribute::factory()->create([
        'type'             => 'textarea',
        'enable_wysiwyg'   => 1,
        'value_per_locale' => 1,
    ]);

    $product = Product::factory()->create();

    (new SaveTranslatedDataJob(
        $product->id,
        [['locale' => 'fr_FR', 'content' => '<p>Bonjour</p><script>alert(1)</script><img src="x" onerror="alert(2)">']],
        'default',
        $attribute->code,
    ))->handle(
        resolve(ProductRepository::class),
        resolve(AttributeRepository::class),
        resolve(ProductValueMapper::class),
    );

    expect(resolve(ProductValueMapper::class)->getScopedValue(
        $product->fresh()->values,
        $attribute,
        'default',
        'fr_FR',
    ))
        ->toContain('<p>Bonjour</p>')
        ->not->toContain('<script')
        ->not->toContain('onerror');
});

it('stores a translated plain-text value as returned', function () {
    $attribute = Attribute::factory()->create([
        'type'             => 'textarea',
        'enable_wysiwyg'   => 0,
        'value_per_locale' => 1,
    ]);

    $product = Product::factory()->create();

    (new SaveTranslatedDataJob(
        $product->id,
        [['locale' => 'fr_FR', 'content' => 'Taille < 5 mm']],
        'default',
        $attribute->code,
    ))->handle(
        resolve(ProductRepository::class),
        resolve(AttributeRepository::class),
        resolve(ProductValueMapper::class),
    );

    expect(resolve(ProductValueMapper::class)->getScopedValue(
        $product->fresh()->values,
        $attribute,
        'default',
        'fr_FR',
    ))->toBe('Taille < 5 mm');
});
