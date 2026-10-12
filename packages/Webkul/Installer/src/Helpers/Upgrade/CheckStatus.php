<?php

namespace Webkul\Installer\Helpers\Upgrade;

enum CheckStatus: string
{
    case Passed = 'passed';

    case Warning = 'warning';

    case Failed = 'failed';

    /**
     * Console style tag used to render the status label.
     */
    public function style(): string
    {
        return match ($this) {
            self::Passed  => 'info',
            self::Warning => 'comment',
            self::Failed  => 'error',
        };
    }

    /**
     * Glyph shown beside the check name in the report table.
     */
    public function glyph(): string
    {
        return match ($this) {
            self::Passed  => '✔',
            self::Warning => '!',
            self::Failed  => '✖',
        };
    }
}
