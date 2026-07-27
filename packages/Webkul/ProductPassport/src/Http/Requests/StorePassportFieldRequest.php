<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Core\Rules\Code;

/**
 * Validates a new passport field, mirroring the canonical attribute quick-create
 * rules so the field is a genuine attribute — the payload/JSON-LD keys off its
 * code, so a bare label would be useless.
 */
class StorePassportFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.mapping');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'        => ['required', 'unique:attributes,code', new Code, new NotSupportedAttributes],
            'type'        => ['required'],
            'swatch_type' => [
                'required_if:type,select,multiselect',
                'prohibited_unless:type,select,multiselect',
                new SwatchTypes,
            ],
        ];
    }
}
