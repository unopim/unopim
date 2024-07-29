<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Files\TemporaryFile;
use Webkul\DataTransfer\Buffer\FileBuffer;

class LocalTemporaryFile extends TemporaryFile
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $writerType;

    /**
     * @var string
     */
    private $LocalFilePath;

    /**
     * @var string|null
     */
    private $temporaryPath;

    public function __construct(string $filePath, string $temporaryPath, $writerType = SpoutWriterFactory::CSV)
    {
        $this->temporaryPath = $temporaryPath;
        Storage::put($filePath, '');
        $this->filePath = $filePath;
        $this->writerType = $writerType;
        $this->LocalFilePath = storage_path(FileBuffer::PUBLIC_STORAGE_PATH.$filePath);
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
     * @param @param string|resource $contents
     */
    public function put($contents)
    {
        file_put_contents($this->filePath, $contents);
    }
}
