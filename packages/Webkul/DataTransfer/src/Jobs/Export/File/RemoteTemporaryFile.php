<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use Illuminate\Support\Arr;
use Maatwebsite\Excel\Files\Disk;
use Maatwebsite\Excel\Files\Filesystem;
use Maatwebsite\Excel\Files\TemporaryFile;

class RemoteTemporaryFile extends TemporaryFile
{
    /**
     * @var string
     */
    private $disk;

    /**
     * @var Disk|null
     */
    private $diskInstance;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var LocalTemporaryFile
     */
    private $localTemporaryFile;

    public function __construct(string $disk, string $filename, LocalTemporaryFile $localTemporaryFile)
    {
        $this->disk = $disk;
        $this->filename = $filename;
        $this->localTemporaryFile = $localTemporaryFile;

        $this->disk()->touch($filename);
    }

    public function __sleep()
    {
        return ['disk', 'filename', 'localTemporaryFile'];
    }

    public function getLocalPath(): string
    {
        return $this->localTemporaryFile->getLocalPath();
    }

    public function existsLocally(): bool
    {
        return $this->localTemporaryFile->exists();
    }

    public function exists(): bool
    {
        return $this->disk()->exists($this->filename);
    }

    public function deleteLocalCopy(): bool
    {
        return $this->localTemporaryFile->delete();
    }

    public function delete(): bool
    {
        // we don't need to delete local copy as it's deleted at end of each chunk
        if (! config('excel.temporary_files.force_resync_remote')) {
            $this->deleteLocalCopy();
        }

        return $this->disk()->delete($this->filename);
    }

    public function sync(): TemporaryFile
    {
        if (! $this->localTemporaryFile->exists()) {
            $this->localTemporaryFile = resolve(TemporaryFileFactory::class)
                ->makeLocal(Arr::last(explode('/', $this->filename)));
        }

        $this->disk()->copy(
            $this,
            $this->localTemporaryFile->getLocalPath()
        );

        return $this;
    }

    /**
     * Store on remote disk.
     */
    public function updateRemote()
    {
        $this->disk()->copy(
            $this->localTemporaryFile,
            $this->filename
        );
    }

    /**
     * @return resource
     */
    public function readStream()
    {
        return $this->disk()->readStream($this->filename);
    }

    public function contents(): string
    {
        return $this->disk()->get($this->filename);
    }

    /**
     * @param  string|resource  $contents
     */
    public function put($contents)
    {
        $this->disk()->put($this->filename, $contents);
    }

    public function disk(): Disk
    {
        return $this->diskInstance ?: $this->diskInstance = app(Filesystem::class)->disk($this->disk);
    }
}
