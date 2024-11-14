<?php

namespace Webkul\Core\Filesystem;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Traits\Sanitizer;

/**
 * Filestorer class allows storing of file through the Storage Facade
 */
class FileStorer
{
    use Sanitizer;

    const HASHED_FOLDER_NAME_KEY = 'hashedFolderName';

    /**
     * To store file in a filesystem
     *
     * To use a specific disk like private or s3 cloud send disk parameter in options
     *
     * Add hashedFolderName as parameter with value true to add an specific folder after path
     */
    public function store(string $path, mixed $file, array $options = [])
    {
        $name = $this->getFileName($file);

        return $this->storeAs($path, $name, $file, $options);
    }

    /**
     * store file with custom name
     */
    public function storeAs(string $path, string $name, mixed $file, array $options = [])
    {
        $disk = Arr::pull($options, 'disk');

        $hashedFolderName = Arr::pull($options, self::HASHED_FOLDER_NAME_KEY);

        $path = $hashedFolderName ? $this->addHashedFolderName($path) : $path;

        $path = Storage::disk($disk)->putFileAs($path, $file, $name, $options);

        $this->sanitizeSVG($path, $file->getMimeType(), $disk);

        return $path;
    }

    /**
     * Retreive file name according to File class
     */
    protected function getFileName(mixed $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }

        if ($file instanceof File) {
            return $file->getName();
        }

        return Str::random(40);
    }

    /**
     * Add random string to path
     */
    protected function addHashedFolderName(string $path): string
    {
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        return $path.Str::random(40);
    }
}
