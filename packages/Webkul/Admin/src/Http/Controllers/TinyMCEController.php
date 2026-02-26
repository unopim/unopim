<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Core\Filesystem\FileStorer;

class TinyMCEController extends Controller
{
    /**
     * Storage folder path.
     */
    private string $storagePath = 'tinymce';

    /**
     * Allowed image extensions (Allow-list approach).
     */
    private array $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
    ];

    /**
     * Allowed MIME types.
     */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function __construct(protected FileStorer $fileStorer) {}

    /**
     * Upload file from TinyMCE.
     */
    public function upload(Request $request)
    {
        if (! auth('admin')->check()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        try {
            $media = $this->storeMedia($request);

            return response()->json([
                'location' => $media['file_url'],
            ]);

        } catch (\Throwable $e) {

            Log::error('TinyMCE Upload Error', [
                'message' => $e->getMessage(),
                'user_id' => auth('admin')->id(),
                'ip'      => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Upload failed',
            ], 500);
        }
    }

    /**
     * Securely store uploaded media.
     */
    private function storeMedia(Request $request): array
    {
        if (! $request->hasFile('file')) {
            abort(400, 'No file uploaded');
        }

        $file = $request->file('file');

        if (! $file->isValid()) {
            abort(400, 'Invalid file upload');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $this->allowedExtensions)) {

            Log::warning('Blocked file upload (Invalid Extension)', [
                'user_id'   => auth('admin')->id(),
                'ip'        => request()->ip(),
                'extension' => $extension,
            ]);

            abort(403, 'Invalid file type');
        }

        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, $this->allowedMimeTypes)) {

            Log::warning('Blocked file upload (Invalid MIME)', [
                'user_id'  => auth('admin')->id(),
                'ip'       => request()->ip(),
                'mimeType' => $mimeType,
            ]);

            abort(403, 'Invalid file content');
        }

        if (preg_match('/\.(php|phtml|php5|phar|html|js)$/i', $file->getClientOriginalName())) {

            Log::warning('Blocked file upload (Double Extension Attempt)', [
                'user_id' => auth('admin')->id(),
                'ip'      => request()->ip(),
                'name'    => $file->getClientOriginalName(),
            ]);

            abort(403, 'Invalid file name');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            abort(403, 'File size exceeds limit');
        }

        $filename = Str::uuid()->toString() . '.' . $extension;
        $path = $file->storeAs(
            'public/' . $this->storagePath,
            $filename
        );

        return [
            'file'      => $path,
            'file_name' => $filename,
            'file_url'  => Storage::url($path),
        ];
    }
}
