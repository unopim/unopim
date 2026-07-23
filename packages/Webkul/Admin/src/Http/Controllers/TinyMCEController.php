<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Requests\TinyMCEUploadRequest;
use Webkul\Core\Filesystem\FileStorer;

class TinyMCEController extends Controller
{
    /**
     * Storage folder path.
     *
     * @var string
     */
    private $storagePath = 'tinymce';

    /**
     * Return controller instance
     */
    public function __construct(protected FileStorer $fileStorer) {}

    /**
     * Upload file from tinymce.
     */
    public function upload(TinyMCEUploadRequest $request): JsonResponse
    {
        abort_unless($request->authorize(), JsonResponse::HTTP_FORBIDDEN);

        $request->validated();

        $media = $this->storeMedia($request);

        if (! empty($media)) {
            return response()->json([
                'location' => $media['file_url'],
            ]);
        }

        return response()->json([]);
    }

    /**
     * Store media.
     */
    public function storeMedia(?Request $request = null): array
    {
        $request ??= request();

        if (! $request->hasFile('file')) {
            return [];
        }

        $file = $request->file('file');

        $name = Str::random(40).'.'.$file->getClientOriginalExtension();

        $path = $this->fileStorer->storeAs(path: $this->storagePath, name: $name, file: $file);

        return [
            'file'      => $path,
            'file_name' => $name,
            'file_url'  => Storage::url($path),
        ];
    }
}
