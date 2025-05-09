<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

class JSONFileBuffer implements \Iterator
{
    const FILE_PREFIX = 'unopim_buffer_';

    /** @var string */
    protected $filename;

    /** @var \SplFileObject|null */
    protected $file;

    public function __construct(string $filename = null)
    {
        $this->filename = tempnam(sys_get_temp_dir(), $filename);
        $this->openFile();
    }

    protected function openFile(): void
    {
        $this->file = new \SplFileObject($this->filename, 'a+');
        $this->file->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
    }

    public static function load(string $filename): self
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("File not found: $filename");
        }

        $instance = new self(); // uses constructor to generate dummy file
        $instance->filename = $filename;
        $instance->openFile();

        return $instance;
    }

    public function write($item, array $options = [])
    {
        if (!is_array($item) && !is_scalar($item)) {
            throw new \Exception(
                sprintf('%s only supports items of type scalar or array', __CLASS__)
            );
        }

        $this->file->fwrite(json_encode($item) . PHP_EOL);
    }

    // Iterator methods
    public function current(): mixed
    {
        return json_decode($this->file->current(), true);
    }

    public function next(): void
    {
        $this->file->next();
    }

    public function key(): int
    {
        return $this->file->key();
    }

    public function valid(): bool
    {
        return $this->file->valid();
    }

    public function rewind(): void
    {
        $this->file->rewind();
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function __sleep(): array
    {
        // Exclude non-serializable SplFileObject
        return ['filename'];
    }

    public function __wakeup(): void
    {
        $this->openFile(); // Reopen after deserialization
    }

    public function __destruct()
    {
        unset($this->file);
        // File cleanup is manual to avoid premature deletion
    }
}
