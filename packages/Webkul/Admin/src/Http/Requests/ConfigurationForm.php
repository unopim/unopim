<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfigurationForm extends FormRequest
{
    /**
     * Determine if the Configuration is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return collect(request()->input('keys', []))->mapWithKeys(function ($item) {
            $data = json_decode($item, true);

            return collect($data['fields'])->mapWithKeys(function ($field) use ($data) {
                $key = $data['key'].'.'.$field['name'];

                // Check delete key exist in the request
                if (! $this->has($key.'.delete')) {
                    $validation = isset($field['validation']) && $field['validation'] ? $field['validation'] : 'nullable';

                    return [$key => $this->resolveRules($validation)];
                }

                return [];
            })->toArray();
        })->toArray();
    }

    protected function resolveRules(mixed $validation): mixed
    {
        if (! is_array($validation)) {
            return $validation;
        }

        return array_map(
            fn ($rule) => is_string($rule) && is_a($rule, ValidationRule::class, true) ? app($rule) : $rule,
            $validation
        );
    }
}
