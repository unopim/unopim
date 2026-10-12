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
