<?php

it('drops only null, empty string and empty array entries', function () {
    expect(filled_values([
        'null'   => null,
        'empty'  => '',
        'array'  => [],
        'zero'   => 0,
        'string' => '0',
        'false'  => false,
        'space'  => ' ',
        'list'   => ['a'],
    ]))->toBe([
        'zero'   => 0,
        'string' => '0',
        'false'  => false,
        'space'  => ' ',
        'list'   => ['a'],
    ]);
});

it('keeps keys of the remaining entries', function () {
    expect(filled_values([null, '0', '', 'x']))->toBe([1 => '0', 3 => 'x']);
});
