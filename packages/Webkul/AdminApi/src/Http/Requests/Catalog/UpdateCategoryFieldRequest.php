<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Rules\CategoryFieldValidationRules;

class UpdateCategoryFieldRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `type` is immutable on update, so the stored field decides whether an input
     * validation may be sent. An unknown code is left to the controller's 404.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('validation')) {
            $this->merge(['validation' => CategoryFieldValidationRules::normalize($this->input('validation'))]);
        }
    }

    public function rules(): array
    {
        $categoryField = app(CategoryFieldRepository::class)->findOneByField('code', $this->route('code'));

        if (! $categoryField) {
            return [];
        }

        $validation = $this->has('validation')
            ? $this->input('validation')
            : $categoryField->validation;

        $rules = CategoryFieldValidationRules::for($categoryField->type, $validation);

        // A partial update that touches neither key must not be forced to resend
        // the pattern of an already-stored regex validation.
        if (! $this->has('validation') && ! $this->has('regex_pattern')) {
            unset($rules['regex_pattern']);
        }

        return $rules;
    }
}
