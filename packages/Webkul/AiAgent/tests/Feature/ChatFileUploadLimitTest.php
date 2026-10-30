<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Webkul\AiAgent\Http\Requests\ChatRequest;

function chatFileFails(UploadedFile $file): bool
{
    return Validator::make(['files' => [$file]], ['files.*' => (new ChatRequest)->rules()['files.*']])->fails();
}

it('should allow CSV / spreadsheet uploads up to 100MB in the Agentic AI chat (Issue #723)', function () {
    expect(chatFileFails(UploadedFile::fake()->create('catalog.csv', 102400, 'text/csv')))->toBeFalse()
        ->and(chatFileFails(UploadedFile::fake()->create('catalog.csv', 102401, 'text/csv')))->toBeTrue();
});

it('bounds chat file uploads by the configured size', function () {
    config(['ai-agent.chat.max_file_kilobytes' => 2048]);

    expect(chatFileFails(UploadedFile::fake()->create('catalog.csv', 2048, 'text/csv')))->toBeFalse()
        ->and(chatFileFails(UploadedFile::fake()->create('catalog.csv', 2049, 'text/csv')))->toBeTrue();
});
