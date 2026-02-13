<?php

namespace Webkul\Tenant\Exceptions;

use RuntimeException;

class TenantStateTransitionException extends RuntimeException
{
    public string $fromStatus;

    public string $toStatus;

    public function __construct(string $from, string $to)
    {
        $this->fromStatus = $from;
        $this->toStatus = $to;

        parent::__construct("Invalid tenant state transition from '{$from}' to '{$to}'.");
    }
}
