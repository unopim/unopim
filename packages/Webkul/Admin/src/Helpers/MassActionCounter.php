<?php

namespace Webkul\Admin\Helpers;

/**
 * Tallies the rows a datagrid mass action changed against the ones it skipped.
 */
class MassActionCounter
{
    private int $succeeded = 0;

    private int $skipped = 0;

    public function succeeded(): void
    {
        $this->succeeded++;
    }

    public function skipped(): void
    {
        $this->skipped++;
    }

    public function succeededCount(): int
    {
        return $this->succeeded;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }

    public function changedNothing(): bool
    {
        return $this->skipped > 0 && $this->succeeded === 0;
    }

    public function isPartial(): bool
    {
        return $this->skipped > 0 && $this->succeeded > 0;
    }
}
