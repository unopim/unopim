<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Attribute\Support\AttributeValidationRules;
use Webkul\Core\Rules\Code;

/**
 * Validates the admin attribute create form.
 *
 * Rules a package contributes arrive through {@see AttributeValidationRules},
 * which the REST create endpoint runs as well, so a field added by a package is
 * validated the same way however it is submitted.
 */
class AttributeStoreForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return AttributeValidationRules::extend([
            'code'        => ['required', 'unique:attributes,code', new Code, new NotSupportedAttributes],
            'type'        => 'required',
            'swatch_type' => [
                'required_if:type,select,multiselect',
                'prohibited_unless:type,select,multiselect',
                new SwatchTypes,
            ],
        ], $this->all());
    }
}
