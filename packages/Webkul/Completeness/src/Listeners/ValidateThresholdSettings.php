<?php

namespace Webkul\Completeness\Listeners;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidateThresholdSettings
{
    public function handle(): void
    {
        $green = request('system.completeness.green_from');
        $red = request('system.completeness.red_below');

        if ($green === null && $red === null) {
            return;
        }

        $validator = Validator::make(
            [
                'green_from' => $green,
                'red_below'  => $red,
            ],
            [
                'red_below'  => 'required|numeric|min:0|max:100',
                'green_from' => 'required|numeric|min:0|max:100|gt:red_below',
            ],
            [
                'green_from.gt' => trans('completeness::app.config.completeness.green-gt-red'),
            ],
            [
                'green_from' => trans('completeness::app.config.completeness.green-from'),
                'red_below'  => trans('completeness::app.config.completeness.red-below'),
            ]
        );

        if ($validator->fails()) {
            $errors = [];

            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors['system.completeness.'.$field] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }
}
