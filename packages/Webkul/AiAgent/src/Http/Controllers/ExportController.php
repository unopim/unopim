<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\AiAgent\Chat\Tools\ExportProducts;
use Webkul\AiAgent\Http\Requests\DownloadExportRequest;

class ExportController extends Controller
{
    public function __construct(protected ExportProducts $exportProducts) {}

    /**
     * Stream an AI product export back to the admin user who generated it.
     */
    public function download(DownloadExportRequest $request, string $file): StreamedResponse
    {
        $path = $this->exportProducts->exportPath((int) auth()->guard('admin')->id(), $file);

        $disk = Storage::disk(ExportProducts::DISK);

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, 'products-export.'.pathinfo($file, PATHINFO_EXTENSION));
    }
}
