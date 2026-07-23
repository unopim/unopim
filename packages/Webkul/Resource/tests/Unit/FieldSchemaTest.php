<?php

use Webkul\Resource\Support\Field;
use Webkul\Resource\Support\FieldSchema;

it('exposes fields as arrays and merges validation rules', function () {
    $schema = FieldSchema::make([
        Field::text('name')->rules('required|max:255'),
        Field::text('email')->rules('required|email'),
    ]);

    expect($schema->toArray())->toHaveCount(2);
    expect($schema->rules())->toBe([
        'name'  => 'required|max:255',
        'email' => 'required|email',
    ]);
});

it('filters fields (extension hook) without mutating the original', function () {
    $schema = FieldSchema::make([
        Field::text('name'),
        Field::text('secret'),
    ]);

    $filtered = $schema->filter(fn (Field $f) => $f->getName() !== 'secret');

    expect($filtered->toArray())->toHaveCount(1);
    expect($schema->toArray())->toHaveCount(2); // original untouched
});
