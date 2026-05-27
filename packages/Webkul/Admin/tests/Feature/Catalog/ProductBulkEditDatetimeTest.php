<?php

$cellView = __DIR__.'/../../../src/Resources/views/components/bulkedit/cell.blade.php';
$editorView = __DIR__.'/../../../src/Resources/views/components/bulkedit/editor.blade.php';
$datetimeView = __DIR__.'/../../../src/Resources/views/components/bulkedit/type/datetime.blade.php';

it('should dispatch datetime attribute type to the datetime editor in bulk edit', function () use ($cellView, $editorView, $datetimeView) {
    expect(file_exists($datetimeView))->toBeTrue();

    $cell = file_get_contents($cellView);
    expect($cell)->toContain("case 'datetime': return 'v-spreadsheet-datetime';");

    $editor = file_get_contents($editorView);
    expect($editor)->toContain("@include('admin::components.bulkedit.type.datetime')");

    $component = file_get_contents($datetimeView);
    expect($component)->toContain('type="datetime-local"');
    expect($component)->toContain('invalid-datetime');
});
