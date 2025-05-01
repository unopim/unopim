<?php

namespace Webkul\AdminApi\Http\Controllers\API;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Webkul\AdminApi\Traits\ApiResponse;
use Webkul\AdminApi\Traits\HtmlPurifier;
use Webkul\Core\Rules\Code;

class ApiController extends BaseController
{
    use ApiResponse, DispatchesJobs, HtmlPurifier, ValidatesRequests;

    /**
     * This function processes and transforms the 'labels' key in the given request data.
     *
     * @return array
     */
    protected function setLabels(array $requestData, string $labelKey = 'name')
    {
        if (! isset($requestData['labels'])) {
            return $requestData;
        }

        $labels = [];
        foreach ($requestData['labels'] as $key => $value) {
            $labels[$key] = [$labelKey => $value];
        }

        unset($requestData['labels']);
        $requestData = array_merge($requestData, $labels);

        return $requestData;
    }

    /**
     * This function creates a validator instance for a given table with unique 'code' requirement.
     *
     * @return \Illuminate\Contracts\Validation\Validator The validator instance.
     */
    protected function codeRequireWithUniqueValidator(string $table, array $newRules = [])
    {
        $rules = [
            'code' => [
                'required',
                sprintf('unique:%s,code', $table),
                new Code,
            ],
        ];

        $validator = Validator::make(
            request()->all(),
            array_merge($rules, $newRules)
        );

        return $validator;
    }

    /**
     * This function creates a validator instance for the given rules.
     *
     * @return \Illuminate\Contracts\Validation\Validator The validator instance.
     */
    protected function validator(array $Rules = [])
    {
        $validator = Validator::make(
            request()->all(),
            $Rules
        );

        return $validator;
    }
}
