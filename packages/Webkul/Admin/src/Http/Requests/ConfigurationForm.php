<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationForm extends FormRequest
{
    /**
     * Determine if the Configuration is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return collect(request()->input('keys', []))->mapWithKeys(function (string $item) {
            $data = json_decode($item, true);

            return collect($data['fields'])->mapWithKeys(function (array $field) use ($data) {
                $key = $data['key'].'.'.$field['name'];

                // Check delete key exist in the request
                if (! $this->has($key.'.delete')) {
                    $validation = isset($field['validation']) && $field['validation'] ? $field['validation'] : 'nullable';

                    return [$key => $validation];
                }

                return [];
            })->toArray();
        })->toArray();
    }
}
