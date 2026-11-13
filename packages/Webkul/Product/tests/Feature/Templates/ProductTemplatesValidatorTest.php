<?php

use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;
use Webkul\Product\Validator\ProductValuesValidator;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    $this->name = $this->variableAttribute('tpl_name');
    $this->description = $this->templateAttribute('tpl_description');
    $this->shortDescription = $this->templateAttribute('tpl_short_description');
    $this->metaDescription = $this->templateAttribute('tpl_meta_description', ['value_per_locale' => 1]);

    $this->family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($this->family);
    AttributeFamily::factory()->linkAttributesToFamily($this->family, [
        $this->name->id,
        $this->description->id,
        $this->shortDescription->id,
        $this->metaDescription->id,
    ]);

    $this->valuesValidator = app(ProductValuesValidator::class);
});

function templateValidationErrors(callable $validate): array
{
    try {
        $validate();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

it('accepts templates without circular references', function () {
    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate([
        'common' => [
            'tpl_name'              => 'Sunglasses',
            'tpl_description'       => 'About {{ tpl_short_description }}',
            'tpl_short_description' => '{{ tpl_name | upper }}',
        ],
    ]));

    expect($errors)->toBe([]);
});

it('rejects circular references between template attributes on every attribute in the cycle', function () {
    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate([
        'common' => [
            'tpl_description'       => 'About {{ tpl_short_description }}',
            'tpl_short_description' => 'See {{ tpl_description }}',
        ],
    ]));

    expect(array_keys($errors))->toBe(['common.tpl_description', 'common.tpl_short_description'])
        ->and($errors['common.tpl_description'][0])->toBe('Circular template reference detected: tpl_description → tpl_short_description → tpl_description.');
});

it('reports a self reference on the field itself', function () {
    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate([
        'common' => ['tpl_description' => 'About {{ tpl_description }}'],
    ]));

    expect($errors)->toBe(['common.tpl_description' => ['The template cannot reference its own attribute "tpl_description".']]);
});

it('detects cycles across scopes using the scoped value of each attribute', function () {
    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate([
        'common'          => ['tpl_description' => '{{ tpl_meta_description }}'],
        'locale_specific' => ['en_US' => ['tpl_meta_description' => '{{ tpl_description }}']],
    ]));

    expect($errors)->toHaveKey('common.tpl_description')
        ->and($errors)->toHaveKey('locale_specific.en_US.tpl_meta_description');
});

it('combines submitted values with the stored and inherited values of the product', function () {
    $parent = Product::factory()->configurable()->create([
        'attribute_family_id' => $this->family->id,
        'values'              => ['common' => ['tpl_description' => 'About {{ tpl_short_description }}']],
    ]);

    $variant = Product::factory()->create([
        'attribute_family_id' => $this->family->id,
        'parent_id'           => $parent->id,
    ]);

    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate(
        data: ['common' => ['tpl_short_description' => 'See {{ tpl_description }}']],
        productId: $variant->id,
    ));

    expect($errors)->toHaveKey('common.tpl_short_description');
});

it('rejects variables that are not part of the product family', function () {
    $outsider = $this->variableAttribute('tpl_outsider');

    $product = Product::factory()->create(['attribute_family_id' => $this->family->id]);

    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate(
        data: ['common' => ['tpl_description' => '{{ tpl_name }} {{ tpl_outsider }}']],
        productId: $product->id,
    ));

    expect($errors)->toBe(['common.tpl_description' => ['The attribute "tpl_outsider" used in the template does not belong to the product family.']])
        ->and($outsider->exists)->toBeTrue();
});

it('also validates templates on partial updates', function () {
    $errors = templateValidationErrors(fn () => $this->valuesValidator->validateOnlyExistingSectionData([
        'common' => [
            'tpl_description'       => '{{ tpl_short_description }}',
            'tpl_short_description' => '{{ tpl_description }}',
        ],
    ]));

    expect($errors)->toHaveKeys(['common.tpl_description', 'common.tpl_short_description']);
});

it('leaves products without templates untouched', function () {
    $errors = templateValidationErrors(fn () => $this->valuesValidator->validate([
        'common' => ['tpl_name' => 'Sunglasses {not a template}'],
    ]));

    expect($errors)->toBe([]);
});
