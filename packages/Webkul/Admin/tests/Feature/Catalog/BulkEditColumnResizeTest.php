<?php

$headerCell = __DIR__.'/../../../src/Resources/views/components/bulkedit/header-cell.blade.php';
$header = __DIR__.'/../../../src/Resources/views/components/bulkedit/header.blade.php';

it('should expose a wider, reactive column resize handle on the bulk edit header (Issue #715)', function () use ($headerCell, $header) {
    $cellContents = file_get_contents($headerCell);
    $headerContents = file_get_contents($header);

    expect($cellContents)->toContain('w-[10px]');
    expect($cellContents)->toContain('bulkedit-column-resized');
    expect($headerContents)->toContain('columnWidths');
    expect($headerContents)->toContain('bulkedit-column-resized');
});
