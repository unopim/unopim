<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\ValidationTypes;
use Webkul\Attribute\Support\AttributeValidationRules;

class UpdateAttributeRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Rules a package contributes for its own attribute type arrive through
     * {@see AttributeValidationRules}, which is the same set the admin form
     * runs. Listeners need the attribute being written, and this endpoint is
     * addressed by code, so the id is looked up here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'is_required'    => ['sometimes', 'boolean'],
            'enable_wysiwyg' => ['sometimes', 'boolean'],
        ];

        if (! empty($this->input('validation'))) {
            $rules['validation'] = [new ValidationTypes];
        }

        return AttributeValidationRules::extend($rules, $this->all(), $this->attributeId());
    }

    /**
     * The id of the attribute this request addresses, or null when the code in
     * the route matches nothing — the controller answers that with a 404 of its
     * own, so validation should not pre-empt it.
     */
    protected function attributeId(): ?int
    {
        $code = $this->route('code');

        if (! is_string($code) || $code === '') {
            return null;
        }

        return app(AttributeRepository::class)->findOneByField('code', $code)?->id;
    }
}
