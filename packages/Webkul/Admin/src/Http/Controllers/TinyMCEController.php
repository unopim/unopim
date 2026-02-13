<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Tenant\Filesystem\TenantStorage;

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
    public function upload()
    {
        $media = $this->storeMedia();

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
     * @return array
     */
    public function storeMedia()
    {
        if (! request()->hasFile('file')) {
            return [];
        }

        $path = $this->fileStorer->store(file: request()->file('file'), path: TenantStorage::path($this->storagePath));

        return [
            'file'      => $path,
            'file_name' => request()->file('file')->getClientOriginalName(),
            'file_url'  => Storage::url($path),
        ];
    }
}
