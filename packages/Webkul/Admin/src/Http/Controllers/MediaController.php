<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Requests\MediaDownloadRequest;
use Webkul\Core\Rules\FileOrImageValidValue;

class MediaController extends Controller
{
    /**
     * Serve a media asset from the default disk as an attachment download.
     */
    public function download(MediaDownloadRequest $request): BinaryFileResponse|StreamedResponse
    {
        $path = $this->normalize((string) $request->validated('path'));

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            abort_if($segment === '.' || $segment === '..' || $segment === '', 404);
        }

        $roots = (array) config('admin.media.downloadable_roots', []);

        abort_unless(array_key_exists($segments[0], $roots), 404);

        abort_unless(bouncer()->hasPermission($roots[$segments[0]]), Response::HTTP_FORBIDDEN, trans('admin::app.common.unauthorized'));

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, $this->allowedExtensions(), true), Response::HTTP_FORBIDDEN);

        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, basename($path));
    }

    /**
     * Normalise a logical storage path.
     */
    private function normalize(string $path): string
    {
        abort_if(str_contains($path, "\0"), 404);

        $path = str_replace('\\', '/', $path);

        return ltrim($path, '/');
    }

    /**
     * Extensions the upload validators accept.
     *
     * @return array<int, string>
     */
    private function allowedExtensions(): array
    {
        return array_map('strtolower', array_merge(
            FileOrImageValidValue::IMAGE_ALLOWED_EXTENSIONS,
            FileOrImageValidValue::VIDEO_ALLOWED_EXTENSIONS,
            FileOrImageValidValue::FILE_ALLOWED_EXTENSION,
        ));
    }
}
