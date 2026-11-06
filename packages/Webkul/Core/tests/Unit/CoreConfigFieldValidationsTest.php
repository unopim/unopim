<?php

use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Rules\IpPatternRule;

it('drops the Laravel-only nullable token so vee-validate never sees an unregistered rule', function () {
    $validations = app(CoreConfigRepository::class)->getValidations([
        'validation' => ['nullable', 'string'],
    ]);

    expect($validations)->toBe('string');
});

it('drops nullable even when it is the only validation entry', function () {
    $validations = app(CoreConfigRepository::class)->getValidations([
        'validation' => ['nullable'],
    ]);

    expect($validations)->toBe('');
});

it('still filters out rule class references alongside nullable', function () {
    $validations = app(CoreConfigRepository::class)->getValidations([
        'validation' => ['nullable', IpPatternRule::class],
    ]);

    expect($validations)->toBe('');
});

it('maps Laravel rule names vee-validate does not know to their vee-validate names', function (string|array $laravel, string $vee) {
    expect(app(CoreConfigRepository::class)->getValidations(['validation' => $laravel]))->toBe($vee);
})->with([
    'in'           => ['required|in:pim,dam', 'required|one_of:pim,dam'],
    'not in'       => ['not_in:a,b', 'not_one_of:a,b'],
    'min'          => ['numeric|min:1', 'numeric|min_value:1'],
    'string rules' => ['nullable|string|max:20', 'nullable|string|max:20'],
    'array rules'  => [['nullable', 'in:x,y'], 'one_of:x,y'],
]);

it('leaves value-list rules to the server on option fields, whose client value is the option object', function (string $type) {
    expect(app(CoreConfigRepository::class)->getValidations(['type' => $type, 'validation' => 'required|in:pim,dam']))->toBe('required');
})->with(['select', 'multiselect']);
