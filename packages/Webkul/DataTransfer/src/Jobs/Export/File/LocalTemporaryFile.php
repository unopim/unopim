<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Files\TemporaryFile;
use Webkul\DataTransfer\Buffer\FileBuffer;

class LocalTemporaryFile extends TemporaryFile
{
    private readonly string $LocalFilePath;

    /**
     * @param  string  $writerType
     */
    public function __construct(private readonly string $filePath, private readonly string $temporaryPath, private $writerType = SpoutWriterFactory::CSV)
    {
        $this->LocalFilePath = storage_path(FileBuffer::PRIVATE_STORAGE_PATH.$this->filePath);

        // The writer opens LocalFilePath directly, so ensure its own directory exists.
        // Storage::makeDirectory() resolves against the default disk (which may be rooted
        // elsewhere, e.g. app/public), leaving app/private/... uncreated and fopen failing.
        File::ensureDirectoryExists(dirname($this->LocalFilePath));
    }

    public function getLocalPath(): string
    {
        return $this->LocalFilePath;
    }

    public function getTemporaryPath(): string
    {
        return $this->temporaryPath;
    }

    public function getWriterType(): string
    {
        return $this->writerType;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function exists(): bool
    {
        return file_exists($this->filePath);
    }

    public function delete(): bool
    {
        if (@unlink($this->filePath) || ! $this->exists()) {
            return true;
        }

        return unlink($this->filePath);
    }

    /**
     * @return resource
     */
    public function readStream()
    {
        return fopen($this->getLocalPath(), 'rb+');
    }

    public function contents(): string
    {
        return file_get_contents($this->filePath);
    }

    /**
     * @param  string|resource  $contents
     */
    public function put($contents): void
    {
        file_put_contents($this->filePath, $contents);
    }
}
