<?php

dataset('datagrid views', [
    'filters'         => 'datagrid/filters.blade.php',
    'toolbar'         => 'datagrid/toolbar.blade.php',
    'history toolbar' => 'history/toolbar.blade.php',
]);

it('gives every visible text input an accessible name', function (string $view) {
    $source = file_get_contents(__DIR__.'/../../../src/Resources/views/components/'.$view);

    preg_match_all('/<input\b(?:[^>]|=>)*?\/?>/s', $source, $matches);

    $unnamed = array_filter($matches[0], fn (string $tag): bool => ! preg_match('/type="(hidden|checkbox|radio|file)"/', $tag)
        && ! preg_match('/[\s:](aria-label|id)=/', $tag));

    expect($unnamed)->toBe([]);
})->with('datagrid views');
