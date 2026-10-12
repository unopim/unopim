<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

function submitProductForm(string $uri, array $parameters, array $files = [])
{
    return submitMultipartForm($uri, 'PUT', $parameters, $files);
}

function productWithMediaAttribute(string $type = 'image', array $attributes = []): array
{
    $attribute = Attribute::factory()->create(['type' => $type] + $attributes);

    $product = seedRequiredProductValues(Product::factory()->simple()->create());

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    return [$product, $attribute->code];
}

function scopedProductValue(Product $product, string $scope, string $code, array $path = [])
{
    $values = $product->values[$scope] ?? [];

    foreach ($path as $segment) {
        $values = $values[$segment] ?? [];
    }

    return $values[$code] ?? null;
}

describe('common scope', function () {
    it('keeps the replacement when an existing product image is re-uploaded', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toContain('first.jpg');

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => '']]], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('second.jpg')]]],
        ]);

        $product->refresh();

        $second = $product->values['common'][$code] ?? null;

        expect($second)->toContain('second.jpg')
            ->and(Storage::exists($second))->toBeTrue();
    });

    it('replaces the image again on every following save', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        foreach (['first.jpg', 'second.jpg', 'third.jpg', 'fourth.jpg'] as $index => $name) {
            $parameters = ['sku' => $product->sku];

            if ($index > 0) {
                $parameters['values'] = ['common' => [$code => '']];
            }

            submitProductForm($uri, $parameters, [
                'values' => ['common' => [$code => [UploadedFile::fake()->image($name)]]],
            ]);

            $product->refresh();

            expect($product->values['common'][$code] ?? null)->toContain($name);
        }
    });

    it('clears the product image when it is removed without a replacement', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toContain('first.jpg');

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => '']]]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toBeNull();
    });

    it('preserves the product image when the form is saved without touching it', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $product->refresh();

        $stored = $product->values['common'][$code];

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => $stored]]], [
            'values' => ['common' => [$code => [0 => null]]],
        ]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toBe($stored);
    });

    it('does not touch the other attributes of the scope while replacing', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => '']]], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('second.jpg')]]],
        ]);

        $product->refresh();

        expect($product->values['common']['sku'])->toBe($product->sku)
            ->and($product->values['common']['url_key'])->not->toBeEmpty()
            ->and($product->values['common'][$code])->toContain('second.jpg');
    });
});

describe('file and gallery attributes', function () {
    it('keeps the replacement when an existing product file is re-uploaded', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute('file');

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->create('first.pdf', 100)]]],
        ]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toContain('first.pdf');

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => '']]], [
            'values' => ['common' => [$code => [UploadedFile::fake()->create('second.pdf', 100)]]],
        ]);

        $product->refresh();

        $second = $product->values['common'][$code] ?? null;

        expect($second)->toContain('second.pdf')
            ->and(Storage::exists($second))->toBeTrue();
    });

    it('adds a new gallery image while keeping the ones already stored', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute('gallery');

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ]]],
        ]);

        $product->refresh();

        $stored = $product->values['common'][$code];

        expect($stored)->toHaveCount(2);

        submitProductForm($uri, [
            'sku'    => $product->sku,
            'values' => ['common' => [$code => [0 => $stored[0], 1 => $stored[1]]]],
        ], [
            'values' => ['common' => [$code => [0 => null, 1 => null, 2 => UploadedFile::fake()->image('three.jpg')]]],
        ]);

        $product->refresh();

        $updated = $product->values['common'][$code];

        expect($updated)->toHaveCount(3)
            ->and($updated[0])->toBe($stored[0])
            ->and($updated[1])->toBe($stored[1])
            ->and($updated[2])->toContain('three.jpg');
    });

    it('replaces one gallery image in place and leaves its neighbour alone', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute('gallery');

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ]]],
        ]);

        $product->refresh();

        $stored = $product->values['common'][$code];

        submitProductForm($uri, [
            'sku'    => $product->sku,
            'values' => ['common' => [$code => [0 => $stored[0]]]],
        ], [
            'values' => ['common' => [$code => [0 => null, 1 => UploadedFile::fake()->image('replaced.jpg')]]],
        ]);

        $product->refresh();

        $updated = $product->values['common'][$code];

        expect($updated)->toHaveCount(2)
            ->and($updated[0])->toBe($stored[0])
            ->and($updated[1])->toContain('replaced.jpg');
    });
});

describe('channel and locale scopes', function () {
    it('replaces a channel and locale specific image without touching the other scope', function () {
        $this->loginAsAdmin();

        Locale::whereIn('code', ['fr_FR', 'de_DE'])->update(['status' => 1]);

        $newChannel = Channel::factory()->create();

        $newChannelCode = $newChannel->code;

        $newChannelLocale = $newChannel->locales->first()->code;

        $defaultChannel = core()->getDefaultChannel();

        $defaultChannelCode = $defaultChannel->code;

        $defaultLocale = $defaultChannel->locales->first()->code;

        [$product, $code] = productWithMediaAttribute('image', [
            'value_per_locale'  => true,
            'value_per_channel' => true,
        ]);

        Storage::fake();

        $defaultUri = route('admin.catalog.products.update', [
            'id'      => $product->id,
            'channel' => $defaultChannelCode,
            'locale'  => $defaultLocale,
        ]);

        $otherUri = route('admin.catalog.products.update', [
            'id'      => $product->id,
            'channel' => $newChannelCode,
            'locale'  => $newChannelLocale,
        ]);

        submitProductForm($defaultUri, ['sku' => $product->sku], [
            'values' => ['channel_locale_specific' => [$defaultChannelCode => [$defaultLocale => [$code => [UploadedFile::fake()->image('default-first.jpg')]]]]],
        ]);

        submitProductForm($otherUri, ['sku' => $product->sku], [
            'values' => ['channel_locale_specific' => [$newChannelCode => [$newChannelLocale => [$code => [UploadedFile::fake()->image('other-first.jpg')]]]]],
        ]);

        $product->refresh();

        $defaultStored = scopedProductValue($product, 'channel_locale_specific', $code, [$defaultChannelCode, $defaultLocale]);

        expect($defaultStored)->toContain('default-first.jpg')
            ->and(scopedProductValue($product, 'channel_locale_specific', $code, [$newChannelCode, $newChannelLocale]))->toContain('other-first.jpg');

        submitProductForm($otherUri, [
            'sku'    => $product->sku,
            'values' => ['channel_locale_specific' => [$newChannelCode => [$newChannelLocale => [$code => '']]]],
        ], [
            'values' => ['channel_locale_specific' => [$newChannelCode => [$newChannelLocale => [$code => [UploadedFile::fake()->image('other-second.jpg')]]]]],
        ]);

        $product->refresh();

        expect(scopedProductValue($product, 'channel_locale_specific', $code, [$newChannelCode, $newChannelLocale]))->toContain('other-second.jpg')
            ->and(scopedProductValue($product, 'channel_locale_specific', $code, [$defaultChannelCode, $defaultLocale]))->toBe($defaultStored);
    });

    it('replaces a locale specific image without touching the other locale', function () {
        $this->loginAsAdmin();

        Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);

        $channel = Channel::factory()->create();

        $firstLocale = $channel->locales->first()->code;

        $secondLocale = $channel->locales->last()->code;

        expect($firstLocale)->not->toBe($secondLocale);

        [$product, $code] = productWithMediaAttribute('image', ['value_per_locale' => true]);

        Storage::fake();

        $firstUri = route('admin.catalog.products.update', [
            'id'      => $product->id,
            'channel' => $channel->code,
            'locale'  => $firstLocale,
        ]);

        $secondUri = route('admin.catalog.products.update', [
            'id'      => $product->id,
            'channel' => $channel->code,
            'locale'  => $secondLocale,
        ]);

        submitProductForm($firstUri, ['sku' => $product->sku], [
            'values' => ['locale_specific' => [$firstLocale => [$code => [UploadedFile::fake()->image('first-locale.jpg')]]]],
        ]);

        submitProductForm($secondUri, ['sku' => $product->sku], [
            'values' => ['locale_specific' => [$secondLocale => [$code => [UploadedFile::fake()->image('second-locale-first.jpg')]]]],
        ]);

        $product->refresh();

        $firstStored = scopedProductValue($product, 'locale_specific', $code, [$firstLocale]);

        expect($firstStored)->toContain('first-locale.jpg')
            ->and(scopedProductValue($product, 'locale_specific', $code, [$secondLocale]))->toContain('second-locale-first.jpg');

        submitProductForm($secondUri, [
            'sku'    => $product->sku,
            'values' => ['locale_specific' => [$secondLocale => [$code => '']]],
        ], [
            'values' => ['locale_specific' => [$secondLocale => [$code => [UploadedFile::fake()->image('second-locale-second.jpg')]]]],
        ]);

        $product->refresh();

        expect(scopedProductValue($product, 'locale_specific', $code, [$secondLocale]))->toContain('second-locale-second.jpg')
            ->and(scopedProductValue($product, 'locale_specific', $code, [$firstLocale]))->toBe($firstStored);
    });

    it('replaces a channel specific image without touching the other channel', function () {
        $this->loginAsAdmin();

        $newChannel = Channel::factory()->create();

        $newChannelCode = $newChannel->code;

        $defaultChannelCode = core()->getDefaultChannel()->code;

        [$product, $code] = productWithMediaAttribute('image', ['value_per_channel' => true]);

        Storage::fake();

        $defaultUri = route('admin.catalog.products.update', ['id' => $product->id, 'channel' => $defaultChannelCode]);

        $otherUri = route('admin.catalog.products.update', ['id' => $product->id, 'channel' => $newChannelCode]);

        submitProductForm($defaultUri, ['sku' => $product->sku], [
            'values' => ['channel_specific' => [$defaultChannelCode => [$code => [UploadedFile::fake()->image('default-first.jpg')]]]],
        ]);

        submitProductForm($otherUri, ['sku' => $product->sku], [
            'values' => ['channel_specific' => [$newChannelCode => [$code => [UploadedFile::fake()->image('other-first.jpg')]]]],
        ]);

        $product->refresh();

        $defaultStored = scopedProductValue($product, 'channel_specific', $code, [$defaultChannelCode]);

        submitProductForm($otherUri, [
            'sku'    => $product->sku,
            'values' => ['channel_specific' => [$newChannelCode => [$code => '']]],
        ], [
            'values' => ['channel_specific' => [$newChannelCode => [$code => [UploadedFile::fake()->image('other-second.jpg')]]]],
        ]);

        $product->refresh();

        expect(scopedProductValue($product, 'channel_specific', $code, [$newChannelCode]))->toContain('other-second.jpg')
            ->and(scopedProductValue($product, 'channel_specific', $code, [$defaultChannelCode]))->toBe($defaultStored);
    });
});

describe('validation', function () {
    it('rejects a replacement with a disallowed extension and keeps the stored image', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $product->refresh();

        $stored = $product->values['common'][$code];

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => '']]], [
            'values' => ['common' => [$code => [UploadedFile::fake()->create('payload.php', 10)]]],
        ]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toBe($stored);
    });

    it('rejects a replacement that is not an image for an image attribute', function () {
        $this->loginAsAdmin();

        [$product, $code] = productWithMediaAttribute();

        Storage::fake();

        $uri = route('admin.catalog.products.update', $product->id);

        submitProductForm($uri, ['sku' => $product->sku], [
            'values' => ['common' => [$code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $product->refresh();

        $stored = $product->values['common'][$code];

        submitProductForm($uri, ['sku' => $product->sku, 'values' => ['common' => [$code => '']]], [
            'values' => ['common' => [$code => [UploadedFile::fake()->create('notes.txt', 10)]]],
        ]);

        $product->refresh();

        expect($product->values['common'][$code] ?? null)->toBe($stored);
    });
});
