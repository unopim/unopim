<?php

namespace Webkul\AdminApi\Http\Requests\Concerns;

use Webkul\Core\Rules\WithoutUploadedFiles;

/**
 * Shape rules for a category's `additional_data` payload, shared by every category write request.
 */
trait ValidatesCategoryAdditionalData
{
    /**
     * Rules that keep `additional_data` and its locale buckets as arrays free of uploaded files.
     *
     * Category files go through the category media endpoint, which enforces the extension allowlist
     * and active-content checks that a file placed directly in `additional_data` would bypass.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function additionalDataRules(): array
    {
        return [
            'additional_data'                   => ['sometimes', 'array', new WithoutUploadedFiles],
            'additional_data.common'            => ['sometimes', 'array'],
            'additional_data.locale_specific'   => ['sometimes', 'array'],
            'additional_data.locale_specific.*' => ['array'],
        ];
    }
}
