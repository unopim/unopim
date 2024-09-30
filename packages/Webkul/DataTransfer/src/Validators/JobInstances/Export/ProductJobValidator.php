<?php

namespace Webkul\DataTransfer\Validators\JobInstances\Export;

use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class ProductJobValidator extends JobValidator
{
    /**
     * Stores validation rules for data
     */
    protected array $rules = [
        'filters.file_format' => 'required',
        'filters.with_media'  => 'in:1,0',
    ];

    /**
     * Names to be used for attributes during generation of error message
     */
    protected array $attributeNames = [
        'filters.file_format' => 'File Format',
        'filters.with_media'  => 'With Media',
    ];
}
