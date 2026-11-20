<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Chat\Tools\ExportProducts;

it('deletes expired chat uploads kept in per-conversation folders', function () {
    $directory = storage_path('app/public/ai-agent/images/0/'.sha1('cleanup-test'));
    File::ensureDirectoryExists($directory);

    $expired = $directory.'/expired.png';
    $recent = $directory.'/recent.png';
    File::put($expired, 'x');
    File::put($recent, 'x');
    touch($expired, now()->subDays(8)->timestamp);

    $this->artisan('ai-agent:cleanup')->assertSuccessful();

    expect(File::exists($expired))->toBeFalse()
        ->and(File::exists($recent))->toBeTrue();

    File::deleteDirectory(dirname($directory));
});

it('deletes expired ai exports from the private export disk', function (): void {
    Storage::fake(ExportProducts::DISK);

    $disk = Storage::disk(ExportProducts::DISK);
    $disk->put(ExportProducts::DIRECTORY.'/1/expired.csv', 'x');
    $disk->put(ExportProducts::DIRECTORY.'/1/recent.csv', 'x');
    touch($disk->path(ExportProducts::DIRECTORY.'/1/expired.csv'), now()->subDays(8)->timestamp);

    $this->artisan('ai-agent:cleanup')->assertSuccessful();

    expect($disk->exists(ExportProducts::DIRECTORY.'/1/expired.csv'))->toBeFalse()
        ->and($disk->exists(ExportProducts::DIRECTORY.'/1/recent.csv'))->toBeTrue();
});
