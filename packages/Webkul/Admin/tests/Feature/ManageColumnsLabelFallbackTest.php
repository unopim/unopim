<?php

/**
 * Regression: attributes that have a label in some locale but not in the
 * current one previously rendered the locale-fallback label in the
 * "available columns" popup (which uses the locale-fallback chain) but
 * showed "[code]" in the grid once applied (the grid only checked the
 * current locale). The popup and the grid must agree.
 */

use Webkul\Admin\Traits\AttributeColumnTrait;
use Webkul\Attribute\Models\Attribute;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('falls back to a non-current-locale label for the column when the current locale is empty', function () {
    $attribute = Attribute::factory()->create(['type' => 'text']);

    $attribute->translations()->delete();

    $translation = $attribute->translations()->make(['name' => 'Deutscher Name']);
    $translation->locale = 'de_DE';
    $translation->save();

    $attribute->load('translations');

    expect($attribute->getTranslatedValueWithFallback('name', 'en_US'))
        ->toBe('Deutscher Name');

    $trait = new class
    {
        use AttributeColumnTrait {
            buildColumnDefinition as public;
        }
    };

    $column = $trait->buildColumnDefinition($attribute);

    expect($column['label'])->toBe('Deutscher Name');
});

it('returns the code placeholder when no translation exists in any locale', function () {
    $attribute = Attribute::factory()->create(['type' => 'text']);

    $attribute->translations()->delete();
    $attribute->load('translations');

    expect($attribute->getTranslatedValueWithFallback('name', 'en_US'))->toBeNull();

    $trait = new class
    {
        use AttributeColumnTrait {
            buildColumnDefinition as public;
        }
    };

    $column = $trait->buildColumnDefinition($attribute);

    expect($column['label'])->toBe('['.$attribute->code.']');
});
