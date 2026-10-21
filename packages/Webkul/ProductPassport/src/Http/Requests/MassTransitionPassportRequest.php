<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Requests\Concerns\SelectsAllMatching;
use Webkul\Publication\Enums\PublicationStatus;

class MassTransitionPassportRequest extends FormRequest
{
    use SelectsAllMatching;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.withdraw');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->selectionRules(), [
            'indices.*' => ['integer', 'exists:publications,id'],
            'value'     => ['required', Rule::in([PublicationStatus::Withdrawn->value, PublicationStatus::Published->value])],
        ]);
    }
}
