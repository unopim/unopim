<?php

namespace Webkul\ChannelConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mappings'                               => ['required', 'array', 'min:1'],
            'mappings.*.unopim_attribute_code'       => ['required', 'string'],
            'mappings.*.channel_field'               => ['required', 'string'],
            'mappings.*.direction'                   => ['required', Rule::in(['export', 'import', 'both'])],
            'mappings.*.transformation'              => ['nullable', 'array'],
            'mappings.*.locale_mapping'              => ['nullable', 'array'],
        ];
    }
}
