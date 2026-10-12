<?php

$view = __DIR__.'/../../../src/Resources/views/configuration/magic-ai/platform/index.blade.php';

it('should conditionally render the delete icon based on whether the delete action exists (Issue #721)', function () use ($view) {
    $contents = file_get_contents($view);

    expect($contents)->toContain("v-if=\"record.actions.find(a => a.icon === 'icon-delete')\"");
    expect($contents)->toContain("v-if=\"record.actions.find(a => a.icon === 'icon-edit')\"");
});
