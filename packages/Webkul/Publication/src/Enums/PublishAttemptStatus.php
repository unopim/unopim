<?php

namespace Webkul\Publication\Enums;

enum PublishAttemptStatus: string
{
    case Queued = 'queued';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isSettled(): bool
    {
        return $this !== self::Queued;
    }
}
