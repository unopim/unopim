<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductValuesValidator;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));

    $this->validator = app(ProductValuesValidator::class);
    $this->channel = core()->getDefaultChannel()->code;
    $this->locale = core()->getDefaultChannel()->locales->first()->code;

    $this->image = Attribute::factory()->create([
        'type'              => 'image',
        'value_per_channel' => false,
        'value_per_locale'  => false,
    ]);

    $this->gallery = Attribute::factory()->create([
        'type'              => 'gallery',
        'value_per_channel' => false,
        'value_per_locale'  => false,
    ]);

    foreach (['shared/root/front.png', 'shared/root/back.png', 'shared/root/other.png'] as $path) {
        Storage::put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }
});

function productWithValues(array $values): Product
{
    $product = Product::factory()->create();

    $product->values = array_replace_recursive($product->values ?? [], $values);
    $product->saveQuietly();

    return $product;
}

it('accepts a stored image path outside the product directory when it is resubmitted unchanged', function () {
    $product = productWithValues([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ]);

    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ], productId: $product->id);

    expect(true)->toBeTrue();
});

it('accepts stored gallery paths outside the product directory when they are resubmitted unchanged', function () {
    $product = productWithValues([
        AbstractType::COMMON_VALUES_KEY => [$this->gallery->code => ['shared/root/front.png', 'shared/root/back.png']],
    ]);

    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->gallery->code => ['shared/root/back.png', 'shared/root/front.png']],
    ], productId: $product->id);

    expect(true)->toBeTrue();
});

it('rejects a new path outside the product directory', function () {
    $product = productWithValues([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ]);

    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/other.png'],
    ], productId: $product->id);
})->throws(ValidationException::class, 'does not belong to this record');

it('rejects a gallery that adds a new path next to the stored ones', function () {
    $product = productWithValues([
        AbstractType::COMMON_VALUES_KEY => [$this->gallery->code => ['shared/root/front.png']],
    ]);

    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->gallery->code => ['shared/root/front.png', 'shared/root/other.png']],
    ], productId: $product->id);
})->throws(ValidationException::class, 'does not belong to this record');

it('rejects a stored path moved to a different attribute', function () {
    $product = productWithValues([
        AbstractType::COMMON_VALUES_KEY => [$this->gallery->code => ['shared/root/front.png']],
    ]);

    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ], productId: $product->id);
})->throws(ValidationException::class, 'does not belong to this record');

it('rejects a path stored on another product', function () {
    productWithValues([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ]);

    $product = productWithValues([]);

    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ], productId: $product->id);
})->throws(ValidationException::class, 'does not belong to this record');

it('rejects a path stored for another locale of a localizable attribute', function () {
    $attribute = Attribute::factory()->create([
        'type'              => 'image',
        'value_per_channel' => false,
        'value_per_locale'  => true,
    ]);

    $otherLocale = 'xx_XX';

    $product = productWithValues([
        AbstractType::LOCALE_VALUES_KEY => [$otherLocale => [$attribute->code => 'shared/root/front.png']],
    ]);

    $this->validator->validate([
        AbstractType::LOCALE_VALUES_KEY => [$this->locale => [$attribute->code => 'shared/root/front.png']],
    ], productId: $product->id);
})->throws(ValidationException::class, 'does not belong to this record');

it('accepts a path stored for the same channel and locale', function () {
    $attribute = Attribute::factory()->create([
        'type'              => 'image',
        'value_per_channel' => true,
        'value_per_locale'  => true,
    ]);

    $product = productWithValues([
        AbstractType::CHANNEL_LOCALE_VALUES_KEY => [$this->channel => [$this->locale => [$attribute->code => 'shared/root/front.png']]],
    ]);

    $this->validator->validate([
        AbstractType::CHANNEL_LOCALE_VALUES_KEY => [$this->channel => [$this->locale => [$attribute->code => 'shared/root/front.png']]],
    ], productId: $product->id);

    expect(true)->toBeTrue();
});

it('rejects a path outside the product directory when creating a product', function () {
    $this->validator->validate([
        AbstractType::COMMON_VALUES_KEY => [$this->image->code => 'shared/root/front.png'],
    ]);
})->throws(ValidationException::class, 'does not belong to this record');
