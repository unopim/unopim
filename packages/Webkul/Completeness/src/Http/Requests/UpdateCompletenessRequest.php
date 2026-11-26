<?php

namespace Webkul\Completeness\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Completeness\Rules\AttributeInFamily;

class UpdateCompletenessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'familyId'             => ['required', 'integer', 'exists:attribute_families,id'],
            'attributeId'          => ['required', 'integer', new AttributeInFamily($this->integer('familyId'))],
            'channel_requirements' => ['nullable', 'string'],
        ];
    }
}
