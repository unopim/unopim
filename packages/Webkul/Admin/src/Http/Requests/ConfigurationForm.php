<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Webkul\Core\Rules\IpPatternRule;

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
        $rules = collect(request()->input('keys', []))->mapWithKeys(function ($item) {
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

        $rules['general.debug.settings.allowed_ips'] = ['nullable', new IpPatternRule];

        return $rules;
    }

    /**
     * Human-readable names for the fields validated outside the dynamic
     * `keys`-driven rule set, so the generated message reads naturally instead
     * of showing the raw dot-path.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'general.debug.settings.allowed_ips' => Str::before(
                trans('admin::app.configuration.index.general.debug.settings.allowed-ips'),
                ' ('
            ),
        ];
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
