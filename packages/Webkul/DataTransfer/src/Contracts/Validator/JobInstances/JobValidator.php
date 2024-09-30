<?php

namespace Webkul\DataTransfer\Contracts\Validator\JobInstances;

/**
 * Interface JobValidator
 *
 * This interface defines the contract for validating job instance data.
 */
interface JobValidator
{
    /**
     * Validates the job instance data
     */
    public function validate(array $data): void;
}
