<?php

use Webkul\Resource\Support\Field;

it('builds a text field with defaults', function () {
    $field = Field::text('name')->required()->rules('required|max:255');

    expect($field->toArray())->toMatchArray([
        'name'         => 'name',
        'type'         => 'text',
        'required'     => true,
        'rules'        => 'required|max:255',
        'translatable' => false,
    ]);
});

it('builds a select field with options and default', function () {
    $field = Field::select('status')
        ->options([['id' => 'active', 'label' => 'Active']])
        ->default('active');

    $arr = $field->toArray();

    expect($arr['type'])->toBe('select');
    expect($arr['options'])->toHaveCount(1);
    expect($arr['default'])->toBe('active');
});
