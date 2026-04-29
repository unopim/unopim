<?php

namespace Webkul\DataTransfer\Validators\JobInstances\Export;

use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class LocaleJobValidator extends JobValidator
{
    /**
     * Stores validation rules for data
     */
    protected array $rules = [
        'filters.file_format' => 'required',
        'filters.status'      => 'nullable|in:enable,All',
    ];

    /**
     * Names to be used for attributes during generation of error message
     */
    protected array $attributeNames = [
        'filters.file_format' => 'File Format',
        'filters.status'      => 'Status',
    ];
}