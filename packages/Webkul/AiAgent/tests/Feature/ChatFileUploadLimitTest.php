<?php

it('should allow CSV / spreadsheet uploads up to 100MB in the Agentic AI chat (Issue #723)', function () {
    $contents = file_get_contents(__DIR__.'/../../src/Http/Requests/ChatRequest.php');

    expect($contents)->toContain("'files.*'     => ['file', 'mimes:csv,xls,xlsx,txt', 'max:102400'");
});
