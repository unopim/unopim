<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer\Concerns;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\DataTransfer\Services\SampleFiles;

trait DownloadsSampleFile
{
    /**
     * Stream a sample shipped for an import/export type.
     *
     * Samples resolve through the package rather than a disk so they survive a
     * wiped `storage` directory, while a copy published to the `public` disk
     * still wins for installations that tailor their own.
     */
    protected function downloadSampleFile(string $configFile, ?string $type, ?string $key = null, bool $images = false): BinaryFileResponse
    {
        $path = $type ? app(SampleFiles::class)->path($configFile, $type, $key, $images) : null;

        abort_if(! $path, 404);

        return response()->download($path);
    }
}
