<?php

use Illuminate\Support\Facades\File;

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
