<?php

namespace Webkul\DataTransfer\Contracts\Validator\JobInstances;

interface JobValidator
{
    /**
     * Validates the job instance data
     */
    public function validate(mixed $data);
}
