<?php

namespace Webkul\DataTransfer\Tests\Support;

use Webkul\DataTransfer\Helpers\Sources\AbstractSource;

class FilelessTestSource extends AbstractSource
{
    protected int $position = 0;

    public function __construct(protected array $rows = [])
    {
        $first = reset($this->rows);

        $this->columnNames = is_array($first) ? array_keys($first) : [];
        $this->totalColumns = count($this->columnNames);

        array_unshift($this->rows, $this->columnNames);
    }

    protected function getNextRow(): array|bool
    {
        if (! isset($this->rows[$this->position])) {
            return false;
        }

        return $this->rows[$this->position++];
    }

    public function rewind(): void
    {
        $this->position = 0;

        parent::rewind();
    }
}
