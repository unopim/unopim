<?php

namespace Webkul\AdminApi\Http\Requests\Concerns;

/**
 * Shape rules for a category's `additional_data` payload, shared by every category write request.
 */
trait ValidatesCategoryAdditionalData
{
    /**
     * Rules that keep `additional_data` and its locale buckets as arrays.
     *
     * @return array<string, array<int, string>>
     */
    protected function additionalDataRules(): array
    {
        return [
            'additional_data'                   => ['sometimes', 'array'],
            'additional_data.common'            => ['sometimes', 'array'],
            'additional_data.locale_specific'   => ['sometimes', 'array'],
            'additional_data.locale_specific.*' => ['array'],
        ];
    }
}
