<?php

namespace Webkul\Measurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Admin\Http\Requests\Concerns\SelectsAllMatching;

class MassDeleteMeasurementFamilyRequest extends FormRequest
{
    use SelectsAllMatching;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * An empty selection is answered by the controller, so `indices` is not required here.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->selectionRules(), ['indices' => ['sometimes', 'array']]);
    }
}
