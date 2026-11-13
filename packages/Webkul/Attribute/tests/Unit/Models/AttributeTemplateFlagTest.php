<?php

use Illuminate\Support\Facades\Schema;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Presenters\AttributeHistoryPresenter;

it('adds the enable_templates column with a disabled default', function () {
    $attribute = Attribute::factory()->create(['type' => 'text']);

    expect(Schema::hasColumn('attributes', 'enable_templates'))->toBeTrue()
        ->and((int) $attribute->fresh()->enable_templates)->toBe(0)
        ->and($attribute->canHoldTemplate())->toBeFalse();
});

it('holds templates only on plain text and textarea attributes with the flag on', function (array $settings, bool $expected) {
    $attribute = Attribute::factory()->make(['validation' => null, 'is_unique' => 0, ...$settings]);

    expect($attribute->canHoldTemplate())->toBe($expected);
})->with([
    'text'                => [['type' => 'text', 'enable_templates' => 1], true],
    'textarea'            => [['type' => 'textarea', 'enable_templates' => 1], true],
    'rich text'           => [['type' => 'textarea', 'enable_wysiwyg' => 1, 'enable_templates' => 1], true],
    'flag off'            => [['type' => 'text', 'enable_templates' => 0], false],
    'number validation'   => [['type' => 'text', 'validation' => 'number', 'enable_templates' => 1], false],
    'regex validation'    => [['type' => 'text', 'validation' => 'regex', 'enable_templates' => 1], false],
    'unique'              => [['type' => 'text', 'is_unique' => 1, 'enable_templates' => 1], false],
    'price'               => [['type' => 'price', 'enable_templates' => 1], false],
    'select'              => [['type' => 'select', 'enable_templates' => 1], false],
    'date'                => [['type' => 'date', 'enable_templates' => 1], false],
]);

it('identifies rich text attributes', function (string $type, int $wysiwyg, bool $expected) {
    expect(Attribute::factory()->make(['type' => $type, 'enable_wysiwyg' => $wysiwyg])->isRichText())->toBe($expected);
})->with([
    ['textarea', 1, true],
    ['textarea', 0, false],
    ['text', 1, false],
]);

it('records enable_templates changes in the attribute history', function () {
    $result = AttributeHistoryPresenter::representValueForHistory(0, 1, 'enable_templates');

    expect(Attribute::getPresenters())->toHaveKey('enable_templates')
        ->and($result['enable_templates'])->toBe([
            'name' => 'Enable Templates',
            'old'  => 'No',
            'new'  => 'Yes',
        ]);
});
