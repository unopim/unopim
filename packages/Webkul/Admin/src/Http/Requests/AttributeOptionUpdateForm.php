<?php

namespace Webkul\Admin\Http\Requests;

/**
 * Update variant of the option form: same swatch upload validation and SVG
 * scan as create, without the code-uniqueness rule (the code is not editable
 * on update, and the option would collide with itself).
 */
class AttributeOptionUpdateForm extends AttributeOptionForm
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $rules = parent::rules();

        unset($rules['code']);

        return $rules;
    }
}
