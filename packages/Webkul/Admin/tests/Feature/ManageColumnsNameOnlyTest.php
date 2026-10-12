<?php

$view = __DIR__.'/../../src/Resources/views/components/datagrid/manage-columns/index.blade.php';

it('should not render the attribute code next to the name in the manage-columns list (Issue #716)', function () use ($view) {
    $contents = file_get_contents($view);

    expect($contents)->not->toContain("v-text=\"'(' + element.code + ')'\"");
    expect($contents)->not->toContain('v-if="element.code !== element.label"');
});
