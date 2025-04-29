<?php

namespace Webkul\AdminApi\Http\Controllers\API;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Webkul\AdminApi\Traits\ApiResponse;
use Webkul\Core\Rules\Code;
use HTMLPurifier;
use HTMLPurifier_Config;

class ApiController extends BaseController
{
    use ApiResponse, DispatchesJobs, ValidatesRequests;

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
    /**
     * TextareaPurify function to sanitize the textarea input
     * @param mixed $value
     * @return mixed
     */
    protected function textareaPurify(mixed $value): mixed
    {
        $value = htmlspecialchars_decode($value, ENT_QUOTES);
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,img[src|alt|width|height],h2,h3,h4,table,thead,tbody,tr,th,td');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('HTML.SafeIframe', true);
        $config->set('HTML.SafeObject', true);

        $purifier = new HTMLPurifier($config);
        $value = $purifier->purify($value);

        return $value;
    }
}
