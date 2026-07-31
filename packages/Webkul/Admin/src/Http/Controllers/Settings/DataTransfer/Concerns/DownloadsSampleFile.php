<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer\Concerns;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait DownloadsSampleFile
{
    /**
     * Stream the sample file shipped for an import/export type.
     *
     * Samples are always stored on the `public` disk, so the download must not
     * follow `FILESYSTEM_DISK` — an installation pointing the default disk at
     * S3 or `local` would otherwise fail on every sample.
     */
    protected function downloadSampleFile(string $configFile, ?string $type): StreamedResponse
    {
        $path = $type ? config($configFile.'.'.$type.'.sample_path') : null;

        abort_if(! is_string($path) || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path);
    }
}
