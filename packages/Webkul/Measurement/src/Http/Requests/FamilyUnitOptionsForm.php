<?php

namespace Webkul\Measurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyUnitOptionsForm extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.attributes'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'family' => ['required', 'string', Rule::exists('measurement_families', 'code')],
        ];
    }
}
