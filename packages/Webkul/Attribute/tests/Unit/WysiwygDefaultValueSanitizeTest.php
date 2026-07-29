<?php

use Webkul\Attribute\Repositories\AttributeRepository;

it('sanitizes wysiwyg textarea default_value', function () {
    $out = app(AttributeRepository::class)->validateUserInput([
        'type'           => 'textarea',
        'enable_wysiwyg' => 1,
        'default_value'  => '<p>Keep</p><script>alert(1)</script><a href="javascript:alert(2)">x</a>',
    ]);

    expect($out['default_value'])
        ->not->toContain('<script')
        ->not->toContain('javascript:')
        ->toContain('Keep');
});

it('leaves non-wysiwyg textarea default_value untouched', function () {
    $raw = '<p>Raw</p><script>keep()</script>';

    $out = app(AttributeRepository::class)->validateUserInput([
        'type'           => 'textarea',
        'enable_wysiwyg' => 0,
        'default_value'  => $raw,
    ]);

    expect($out['default_value'])->toBe($raw);
});
