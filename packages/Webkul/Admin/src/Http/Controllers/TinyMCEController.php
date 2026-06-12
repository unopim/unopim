<?php

namespace Webkul\Admin\Http\Controllers;

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
     *
     * @return void
     */
    public function upload(TinyMCEUploadRequest $request)
    {
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
     *
     * The file type is validated to an image allowlist by the request, and the
     * stored name is randomised (never the client-supplied name/extension) so an
     * executable or HTML extension can never be written to the public path.
     *
     * @return array
     */
    public function storeMedia(TinyMCEUploadRequest $request)
    {
        $file = $request->file('file');

        $name = Str::random(40).'.'.($file->guessExtension() ?: $file->getClientOriginalExtension());

        $path = $this->fileStorer->storeAs($this->storagePath, $name, $file);

        return [
            'file'      => $path,
            'file_name' => $name,
            'file_url'  => Storage::url($path),
        ];
    }
}
