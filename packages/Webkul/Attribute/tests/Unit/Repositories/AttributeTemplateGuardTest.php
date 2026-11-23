<?php

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;

beforeEach(function () {
    $this->repository = app(AttributeRepository::class);
});

function templateGuardAttributeData(array $overrides = []): array
{
    return [
        'code'              => 'tpl_'.Str::lower(Str::random(8)),
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        ...$overrides,
    ];
}

it('creates text and textarea attributes with templates enabled', function (string $type) {
    $attribute = $this->repository->create(templateGuardAttributeData(['type' => $type, 'enable_templates' => 'on']));

    expect((int) $attribute->fresh()->enable_templates)->toBe(1)
        ->and($attribute->fresh()->canHoldTemplate())->toBeTrue();
})->with(['text', 'textarea']);

it('rejects templates on attributes that cannot hold them', function (array $overrides) {
    expect(fn () => $this->repository->create(templateGuardAttributeData(['enable_templates' => 1, ...$overrides])))
        ->toThrow(ValidationException::class, 'Templates can only be enabled on text and textarea attributes that have no input validation and are not unique.');
})->with([
    'price'      => [['type' => 'price']],
    'select'     => [['type' => 'select', 'swatch_type' => 'dropdown']],
    'validation' => [['validation' => 'email']],
    'unique'     => [['is_unique' => 1]],
]);

it('keeps creating attributes without the flag unchanged', function () {
    $attribute = $this->repository->create(templateGuardAttributeData(['type' => 'price']));

    expect((int) $attribute->fresh()->enable_templates)->toBe(0);
});

it('rejects enabling templates on an existing attribute with input validation', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => 'email']);

    expect(fn () => $this->repository->update(['enable_templates' => 1], $attribute->id))
        ->toThrow(ValidationException::class);
});

it('rejects adding input validation to an attribute that holds templates', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => null, 'enable_templates' => 1]);

    expect(fn () => $this->repository->update(['validation' => 'number'], $attribute->id))
        ->toThrow(ValidationException::class)
        ->and($attribute->fresh()->validation)->toBeNull();
});

it('updates other settings of an attribute that holds templates', function () {
    $attribute = Attribute::factory()->create(['type' => 'textarea', 'validation' => null, 'enable_templates' => 1]);

    $this->repository->update(['enable_wysiwyg' => 1, 'enable_templates' => 1], $attribute->id);

    expect($attribute->fresh())
        ->canHoldTemplate()->toBeTrue()
        ->isRichText()->toBeTrue();
});

it('disables templates on an existing attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => null, 'enable_templates' => 1]);

    $this->repository->update(['enable_templates' => null], $attribute->id);

    expect((int) $attribute->fresh()->enable_templates)->toBe(0);
});
