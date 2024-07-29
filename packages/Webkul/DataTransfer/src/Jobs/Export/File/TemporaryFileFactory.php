<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use Illuminate\Support\Str;

class TemporaryFileFactory
{
    const FILE_PREFIX = 'uno-pim';

    /**
     * @var string|null
     */
    private $temporaryPath;

    /**
     * @var string|null
     */
    private $temporaryDisk;

    public function __construct(?string $temporaryPath = null, ?string $temporaryDisk = null)
    {
        $this->temporaryPath = $temporaryPath;
        $this->temporaryDisk = $temporaryDisk;
    }

    /**
     * @return TemporaryFile
     */
    public function make(?string $fileExtension = null, $fileName = null)
    {
        if ($this->temporaryDisk !== null) {
            return $this->makeRemote($fileExtension);
        }

        return $this->makeLocal(null, $fileExtension);
    }

    public function makeLocal(?string $fileName = null, ?string $fileExtension = null): LocalTemporaryFile
    {
        return new LocalTemporaryFile(
            $this->temporaryPath.DIRECTORY_SEPARATOR.($fileName ?: $this->generateFilename($fileExtension)),
            $this->temporaryPath,
            $fileExtension
        );
    }

    private function makeRemote(?string $fileExtension = null): RemoteTemporaryFile
    {
        $filename = $this->generateFilename($fileExtension);

        return new RemoteTemporaryFile(
            $this->temporaryDisk,
            config('excel.temporary_files.remote_prefix').$filename,
            $this->makeLocal($filename)
        );
    }

    private function generateFilename(?string $fileExtension = null): string
    {
        return self::FILE_PREFIX.Str::random(32).($fileExtension ? '.'.strtolower($fileExtension) : '');
    }
}
