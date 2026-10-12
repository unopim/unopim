<?php

it('registers the help menu item at the bottom', function () {
    $help = collect(config('menu.admin'))->firstWhere('key', 'help');

    expect($help)->not->toBeNull();
    expect($help['route'])->toBe('admin.help.index');
    expect($help['sort'])->toBe(99999);
});

it('registers the help acl entry', function () {
    $help = collect(config('acl'))->firstWhere('key', 'help');

    expect($help)->not->toBeNull();
    expect($help['route'])->toBe('admin.help.index');
});
