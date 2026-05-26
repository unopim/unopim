<?php

$view = __DIR__.'/../../src/Resources/views/components/datagrid/manage-columns/index.blade.php';

it('initializes the Selected Columns panel from visible grid columns only', function () use ($view) {
    $contents = file_get_contents($view);

    expect($contents)->toContain('.filter((el) => el.visible !== false)');
});
