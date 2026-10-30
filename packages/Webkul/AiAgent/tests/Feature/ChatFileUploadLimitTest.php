<?php

use Webkul\AiAgent\Http\Requests\ChatRequest;

it('should allow CSV / spreadsheet uploads up to 100MB in the Agentic AI chat (Issue #723)', function () {
    expect((new ChatRequest)->rules()['files.*'])->toContain('max:102400');
});

it('bounds chat file uploads by the configured size', function () {
    config(['ai-agent.chat.max_file_kilobytes' => 2048]);

    expect((new ChatRequest)->rules()['files.*'])->toContain('max:2048');
});
