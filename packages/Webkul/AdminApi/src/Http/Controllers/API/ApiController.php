<?php

namespace Webkul\AdminApi\Http\Controllers\API;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Webkul\AdminApi\Traits\ApiResponse;
use Webkul\Core\Rules\Code;
use Webkul\Core\Traits\HtmlPurifier;

class ApiController extends BaseController
{
    use ApiResponse, DispatchesJobs, HtmlPurifier, ValidatesRequests;

    /**
     * This function processes and transforms the 'labels' key in the given request data.
     */
    protected function setLabels(array $requestData, string $labelKey = 'name'): array
    {
        if (! isset($requestData['labels'])) {
            return $requestData;
        }

        $labels = [];
        foreach ($requestData['labels'] as $key => $value) {
            $labels[$key] = [$labelKey => $value];
        }

        unset($requestData['labels']);

        return array_merge($requestData, $labels);
    }

    /**
     * This function creates a validator instance for a given table with unique 'code' requirement.
     *
     * @return \Illuminate\Contracts\Validation\Validator The validator instance.
     */
    protected function codeRequireWithUniqueValidator(string $table, array $newRules = []): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [
            'code' => [
                'required',
                sprintf('unique:%s,code', $table),
                new Code,
            ],
        ];

        return Validator::make(
            request()->all(),
            array_merge($rules, $newRules)
        );
    }

    /**
     * This function creates a validator instance for the given rules.
     *
     * @return \Illuminate\Contracts\Validation\Validator The validator instance.
     */
    protected function validator(array $Rules = []): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            request()->all(),
            $Rules
        );
    }
}
