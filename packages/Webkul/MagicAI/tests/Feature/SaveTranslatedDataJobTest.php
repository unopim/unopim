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

/**
 * Run the job that saves a single translated field for the fr_FR locale of the default channel.
 */
function saveFrenchTranslation(Product $product, string $attributeCode, string $content): void
{
    (new SaveTranslatedDataJob($product->id, [['locale' => 'fr_FR', 'content' => $content]], 'default', $attributeCode))
        ->handle(
            resolve(ProductRepository::class),
            resolve(AttributeRepository::class),
            resolve(ProductValueMapper::class),
        );
}

/**
 * Read the stored fr_FR value of the attribute in the default channel.
 */
function storedFrenchTranslation(Product $product, Attribute $attribute): mixed
{
    return resolve(ProductValueMapper::class)->getScopedValue($product->fresh()->values, $attribute, 'default', 'fr_FR');
}

it('writes the translated value into the target locale scope', function () {
    $attribute = translatableAttribute();
    $product = productWithValue($attribute, 'default', 'en_US', 'Source text');

    saveFrenchTranslation($product, $attribute->code, 'Texte traduit');

    expect(storedFrenchTranslation($product, $attribute))->toBe('Texte traduit');
});

it('leaves an already translated value alone unless the install opts into replacing', function () {
    $attribute = translatableAttribute();
    $product = productWithValue($attribute, 'default', 'fr_FR', 'Valeur existante');

    saveFrenchTranslation($product, $attribute->code, 'Nouvelle valeur');

    expect(storedFrenchTranslation($product, $attribute))->toBe('Valeur existante');
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

    saveFrenchTranslation($product, $attribute->code, 'Nouvelle valeur');

    expect(storedFrenchTranslation($product, $attribute))->toBe('Nouvelle valeur');
});

it('does nothing when the field is not a known attribute', function () {
    $product = Product::factory()->create();
    $before = $product->values;

    saveFrenchTranslation($product, 'no_such_attribute', 'x');

    expect($product->fresh()->values)->toEqual($before);
});

it('purifies a translated rich-text value before it is stored', function () {
    $attribute = Attribute::factory()->create(['type' => 'textarea', 'enable_wysiwyg' => 1, 'value_per_locale' => 1]);
    $product = Product::factory()->create();

    saveFrenchTranslation($product, $attribute->code, '<p>Bonjour</p><script>alert(1)</script><img src="x" onerror="alert(2)">');

    expect(storedFrenchTranslation($product, $attribute))
        ->toContain('<p>Bonjour</p>')
        ->not->toContain('<script')
        ->not->toContain('onerror');
});

it('stores a translated plain-text value as returned', function () {
    $attribute = Attribute::factory()->create(['type' => 'textarea', 'enable_wysiwyg' => 0, 'value_per_locale' => 1]);
    $product = Product::factory()->create();

    saveFrenchTranslation($product, $attribute->code, 'Taille < 5 mm');

    expect(storedFrenchTranslation($product, $attribute))->toBe('Taille < 5 mm');
});
