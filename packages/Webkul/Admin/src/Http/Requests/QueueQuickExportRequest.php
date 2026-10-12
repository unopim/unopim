<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Requests\Concerns\SelectsAllMatching;

class QueueQuickExportRequest extends FormRequest
{
    use SelectsAllMatching;

    /**
     * Determine if the request is authorized or not.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->selectionRules(),
            'select_all' => ['required', 'accepted'],
            'format'     => ['required', Rule::in(['csv', 'xls', 'xlsx'])],
        ];
    }
}
