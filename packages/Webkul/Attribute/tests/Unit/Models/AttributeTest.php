<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

describe('Attribute Model - Factory Creation', function () {
    it('creates a text attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'text']);

        expect($attribute)->toBeInstanceOf(Attribute::class)
            ->and($attribute->type)->toBe('text')
            ->and($attribute->exists)->toBeTrue();
    });

    it('creates a textarea attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'textarea']);

        expect($attribute->type)->toBe('textarea');
    });

    it('creates a boolean attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'boolean']);

        expect($attribute->type)->toBe('boolean');
    });

    it('creates a price attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'price']);

        expect($attribute->type)->toBe('price');
    });

    it('creates a select attribute with options via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'select']);

        expect($attribute->type)->toBe('select')
            ->and($attribute->options)->toHaveCount(3);
    });

    it('creates a multiselect attribute with options via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'multiselect']);

        expect($attribute->type)->toBe('multiselect')
            ->and($attribute->options)->toHaveCount(3);
    });

    it('creates a checkbox attribute with options via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'checkbox']);

        expect($attribute->type)->toBe('checkbox')
            ->and($attribute->options)->toHaveCount(3);
    });

    it('creates a datetime attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'datetime']);

        expect($attribute->type)->toBe('datetime');
    });

    it('creates a date attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'date']);

        expect($attribute->type)->toBe('date');
    });

    it('creates a file attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'file']);

        expect($attribute->type)->toBe('file');
    });

    it('creates an image attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'image']);

        expect($attribute->type)->toBe('image');
    });

    it('creates a gallery attribute via factory', function () {
        $attribute = Attribute::factory()->create(['type' => 'gallery']);

        expect($attribute->type)->toBe('gallery');
    });
});

describe('Attribute Model - Locale/Channel Scoping', function () {
    it('returns true for isLocaleBasedAttribute when value_per_locale is set', function () {
        $attribute = Attribute::factory()->create([
            'type'             => 'text',
            'value_per_locale' => true,
        ]);

        expect($attribute->isLocaleBasedAttribute())->toBeTrue();
    });

    it('returns false for isLocaleBasedAttribute when value_per_locale is not set', function () {
        $attribute = Attribute::factory()->create([
            'type'             => 'text',
            'value_per_locale' => false,
        ]);

        expect($attribute->isLocaleBasedAttribute())->toBeFalse();
    });

    it('returns true for isChannelBasedAttribute when value_per_channel is set', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_channel' => true,
        ]);

        expect($attribute->isChannelBasedAttribute())->toBeTrue();
    });

    it('returns false for isChannelBasedAttribute when value_per_channel is not set', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_channel' => false,
        ]);

        expect($attribute->isChannelBasedAttribute())->toBeFalse();
    });

    it('returns true for isLocaleAndChannelBasedAttribute when both are set', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => true,
        ]);

        expect($attribute->isLocaleAndChannelBasedAttribute())->toBeTrue();
    });

    it('returns false for isLocaleAndChannelBasedAttribute when only locale is set', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => false,
        ]);

        expect($attribute->isLocaleAndChannelBasedAttribute())->toBeFalse();
    });

    it('returns false for isLocaleAndChannelBasedAttribute when only channel is set', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => true,
        ]);

        expect($attribute->isLocaleAndChannelBasedAttribute())->toBeFalse();
    });

    it('returns false for isLocaleAndChannelBasedAttribute when neither is set', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        expect($attribute->isLocaleAndChannelBasedAttribute())->toBeFalse();
    });
});

describe('Attribute Model - canBeDeleted', function () {
    it('returns false for sku attribute', function () {
        $skuAttribute = Attribute::where('code', 'sku')->first();

        if (! $skuAttribute) {
            $skuAttribute = Attribute::factory()->create([
                'code' => 'sku',
                'type' => 'text',
            ]);
        }

        expect($skuAttribute->canBeDeleted())->toBeFalse();
    });

    it('returns true for non-sku attributes', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'test_deletable',
            'type' => 'text',
        ]);

        expect($attribute->canBeDeleted())->toBeTrue();
    });
});

describe('Attribute Model - getFilterType', function () {
    it('returns boolean for boolean type', function () {
        $attribute = Attribute::factory()->create(['type' => 'boolean']);

        expect($attribute->getFilterType())->toBe('boolean');
    });

    it('returns datetime_range for datetime type', function () {
        $attribute = Attribute::factory()->create(['type' => 'datetime']);

        expect($attribute->getFilterType())->toBe('datetime_range');
    });

    it('returns date_range for date type', function () {
        $attribute = Attribute::factory()->create(['type' => 'date']);

        expect($attribute->getFilterType())->toBe('date_range');
    });

    it('returns dropdown for select type', function () {
        $attribute = Attribute::factory()->create(['type' => 'select']);

        expect($attribute->getFilterType())->toBe('dropdown');
    });

    it('returns dropdown for multiselect type', function () {
        $attribute = Attribute::factory()->create(['type' => 'multiselect']);

        expect($attribute->getFilterType())->toBe('dropdown');
    });

    it('returns dropdown for checkbox type', function () {
        $attribute = Attribute::factory()->create(['type' => 'checkbox']);

        expect($attribute->getFilterType())->toBe('dropdown');
    });

    it('returns price for price type', function () {
        $attribute = Attribute::factory()->create(['type' => 'price']);

        expect($attribute->getFilterType())->toBe('price');
    });

    it('returns gallery for gallery type', function () {
        $attribute = Attribute::factory()->create(['type' => 'gallery']);

        expect($attribute->getFilterType())->toBe('gallery');
    });

    it('returns image for image type', function () {
        $attribute = Attribute::factory()->create(['type' => 'image']);

        expect($attribute->getFilterType())->toBe('image');
    });

    it('returns string for text type', function () {
        $attribute = Attribute::factory()->create(['type' => 'text']);

        expect($attribute->getFilterType())->toBe('string');
    });

    it('returns string for textarea type', function () {
        $attribute = Attribute::factory()->create(['type' => 'textarea']);

        expect($attribute->getFilterType())->toBe('string');
    });
});

describe('Attribute Model - getScope', function () {
    it('returns common scope when neither locale nor channel based', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        expect($attribute->getScope('en_US', 'default'))->toBe('common');
    });

    it('returns locale_specific scope when only locale based', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => false,
        ]);

        expect($attribute->getScope('en_US', 'default'))->toBe('locale_specific.en_US');
    });

    it('returns channel_specific scope when only channel based', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => true,
        ]);

        expect($attribute->getScope('en_US', 'default'))->toBe('channel_specific.default');
    });

    it('returns channel_locale_specific scope when both locale and channel based', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => true,
        ]);

        expect($attribute->getScope('en_US', 'default'))->toBe('channel_locale_specific.default.en_US');
    });

    it('handles null locale and channel gracefully for common scope', function () {
        $attribute = Attribute::factory()->create([
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        expect($attribute->getScope(null, null))->toBe('common');
    });
});

describe('Attribute Model - getValueFromProductValues', function () {
    it('extracts common value from product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_common',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        $values = [
            'common' => ['test_common' => 'Hello World'],
        ];

        expect($attribute->getValueFromProductValues($values, 'default', 'en_US'))->toBe('Hello World');
    });

    it('extracts locale-specific value from product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_locale',
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => false,
        ]);

        $values = [
            'locale_specific' => [
                'en_US' => ['test_locale' => 'English Value'],
                'fr_FR' => ['test_locale' => 'French Value'],
            ],
        ];

        expect($attribute->getValueFromProductValues($values, 'default', 'en_US'))->toBe('English Value')
            ->and($attribute->getValueFromProductValues($values, 'default', 'fr_FR'))->toBe('French Value');
    });

    it('extracts channel-specific value from product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_channel',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => true,
        ]);

        $values = [
            'channel_specific' => [
                'default' => ['test_channel' => 'Default Channel Value'],
                'web'     => ['test_channel' => 'Web Channel Value'],
            ],
        ];

        expect($attribute->getValueFromProductValues($values, 'default', 'en_US'))->toBe('Default Channel Value')
            ->and($attribute->getValueFromProductValues($values, 'web', 'en_US'))->toBe('Web Channel Value');
    });

    it('extracts channel-and-locale-specific value from product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_both',
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => true,
        ]);

        $values = [
            'channel_locale_specific' => [
                'default' => [
                    'en_US' => ['test_both' => 'Default EN'],
                    'fr_FR' => ['test_both' => 'Default FR'],
                ],
                'web' => [
                    'en_US' => ['test_both' => 'Web EN'],
                ],
            ],
        ];

        expect($attribute->getValueFromProductValues($values, 'default', 'en_US'))->toBe('Default EN')
            ->and($attribute->getValueFromProductValues($values, 'default', 'fr_FR'))->toBe('Default FR')
            ->and($attribute->getValueFromProductValues($values, 'web', 'en_US'))->toBe('Web EN');
    });

    it('returns null when value key does not exist in product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'missing_attr',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        $values = [
            'common' => ['other_attr' => 'Some Value'],
        ];

        expect($attribute->getValueFromProductValues($values, 'default', 'en_US'))->toBeNull();
    });

    it('returns null when scope section is missing from product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_missing_scope',
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => false,
        ]);

        $values = [
            'common' => ['test_missing_scope' => 'Wrong Scope'],
        ];

        expect($attribute->getValueFromProductValues($values, 'default', 'en_US'))->toBeNull();
    });
});

describe('Attribute Model - setProductValue', function () {
    it('sets common value in product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_set_common',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        $productValues = [];
        $attribute->setProductValue('New Value', $productValues);

        expect($productValues)->toBe([
            'common' => ['test_set_common' => 'New Value'],
        ]);
    });

    it('sets locale-specific value in product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_set_locale',
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => false,
        ]);

        $productValues = [];
        $attribute->setProductValue('Locale Value', $productValues, null, 'en_US');

        expect($productValues)->toBe([
            'locale_specific' => [
                'en_US' => ['test_set_locale' => 'Locale Value'],
            ],
        ]);
    });

    it('sets channel-specific value in product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_set_channel',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => true,
        ]);

        $productValues = [];
        $attribute->setProductValue('Channel Value', $productValues, 'default');

        expect($productValues)->toBe([
            'channel_specific' => [
                'default' => ['test_set_channel' => 'Channel Value'],
            ],
        ]);
    });

    it('sets channel-and-locale-specific value in product values', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_set_both',
            'type'              => 'text',
            'value_per_locale'  => true,
            'value_per_channel' => true,
        ]);

        $productValues = [];
        $attribute->setProductValue('Both Value', $productValues, 'default', 'en_US');

        expect($productValues)->toBe([
            'channel_locale_specific' => [
                'default' => [
                    'en_US' => ['test_set_both' => 'Both Value'],
                ],
            ],
        ]);
    });

    it('preserves existing values when setting new ones', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_preserve',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        $productValues = [
            'common' => ['existing_attr' => 'Existing Value'],
        ];

        $attribute->setProductValue('New Value', $productValues);

        expect($productValues['common']['existing_attr'])->toBe('Existing Value')
            ->and($productValues['common']['test_preserve'])->toBe('New Value');
    });

    it('overwrites existing value for same attribute', function () {
        $attribute = Attribute::factory()->create([
            'code'              => 'test_overwrite',
            'type'              => 'text',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        $productValues = [
            'common' => ['test_overwrite' => 'Old Value'],
        ];

        $attribute->setProductValue('New Value', $productValues);

        expect($productValues['common']['test_overwrite'])->toBe('New Value');
    });
});

describe('Attribute Model - Options Relationship', function () {
    it('has options relationship for select attribute', function () {
        $attribute = Attribute::factory()->create(['type' => 'select']);

        expect($attribute->options)->toHaveCount(3)
            ->and($attribute->options->first())->toBeInstanceOf(AttributeOption::class);
    });

    it('has no options for text attribute', function () {
        $attribute = Attribute::factory()->create(['type' => 'text']);

        expect($attribute->options)->toHaveCount(0);
    });
});
