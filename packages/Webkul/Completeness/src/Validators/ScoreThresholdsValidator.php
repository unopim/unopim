<?php

namespace Webkul\Completeness\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Completeness\Services\ScoreThresholds;
use Webkul\Core\Contracts\Validator\ConfigValidator;

class ScoreThresholdsValidator implements ConfigValidator
{
    /**
     * Validate the posted completeness score thresholds.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $options = []): array
    {
        $validator = Validator::make($data, [
            ScoreThresholds::GREEN_FROM_CODE => ['required', 'numeric', 'between:0,100', 'gt:'.ScoreThresholds::RED_BELOW_CODE],
            ScoreThresholds::RED_BELOW_CODE  => ['required', 'numeric', 'between:0,100'],
        ], [
            ScoreThresholds::GREEN_FROM_CODE.'.gt' => trans('completeness::app.configuration.score-thresholds.order-error'),
        ], [
            ScoreThresholds::GREEN_FROM_CODE => trans('completeness::app.configuration.score-thresholds.green-from'),
            ScoreThresholds::RED_BELOW_CODE  => trans('completeness::app.configuration.score-thresholds.red-below'),
        ]);

        throw_if($validator->fails(), ValidationException::class, $validator);

        return $validator->validated();
    }
}
