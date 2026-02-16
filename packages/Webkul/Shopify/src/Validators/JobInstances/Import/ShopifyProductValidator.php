<?php

namespace Webkul\Shopify\Validators\JobInstances\Import;

use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class ShopifyProductValidator extends JobValidator
{
    /**
     * Stores validation rules for data
     */
    protected array $rules = [
        'filters.credentials' => 'required|integer|min:0',
        'filters.locale'      => 'required',
        'filters.channel'     => 'required',
        'filters.currency'    => 'required',
    ];

    /**
     * Names to be used for attributes during generation of error message
     */
    protected array $attributeNames = [
        'filters.credentials' => 'Credentials',
        'filters.locale'      => 'Locale',
        'filters.channel'     => 'Channel',
        'filters.currency'    => 'Currency',
    ];
}
