<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Attribute\Support\AttributeValidationRules;
use Webkul\Core\Rules\Code;

/**
 * Validates the admin attribute edit form.
 *
 * Rules a package contributes arrive through {@see AttributeValidationRules},
 * which the REST update endpoint runs as well. Listeners are given the id of
 * the attribute being saved, because the type is immutable after creation and
 * only the stored row can say what it is.
 */
class AttributeUpdateForm extends FormRequest
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
        $id = $this->attributeId();

        return AttributeValidationRules::extend([
            'code'        => ['required', 'unique:attributes,code,'.$id, new Code],
            'type'        => 'required',
            'swatch_type' => [
                'required_if:type,select,multiselect',
                'prohibited_unless:type,select,multiselect',
                new SwatchTypes,
            ],
        ], $this->all(), $id);
    }

    protected function attributeId(): ?int
    {
        $id = $this->route('id');

        return is_numeric($id) ? (int) $id : null;
    }
}
