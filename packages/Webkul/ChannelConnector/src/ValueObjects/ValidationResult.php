<?php

namespace Webkul\ChannelConnector\ValueObjects;

class ValidationResult
{
    /**
     * @param  array<int, array{field: string, rule: string, message: string}>  $errors
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
    ) {}
}
