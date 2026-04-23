<?php

it('should allow CSV / spreadsheet uploads up to 100MB in the Agentic AI chat (Issue #723)', function () {
    $contents = file_get_contents(__DIR__.'/../../src/Http/Controllers/ChatController.php');

    expect($contents)->toContain("'files.*'     => ['file', 'max:102400'");
});
