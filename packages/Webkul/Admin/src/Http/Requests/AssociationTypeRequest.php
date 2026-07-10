<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Category\Rules\ValidationTypes;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\Product\Rules\AssociationFieldTypes;
use Webkul\Product\Rules\AssociationNotSupportedFields;

class AssociationTypeRequest extends FormRequest
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Determine if the association type request is authorized or not.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'status'              => ['nullable', 'boolean'],
            'position'            => ['nullable', 'integer'],
            'fields'              => ['sometimes', 'array'],
            'fields.*.code'       => ['sometimes', 'required', new AssociationNotSupportedFields],
            'fields.*.type'       => ['sometimes', 'required', new AssociationFieldTypes],
            'fields.*.validation' => ['sometimes', 'nullable', new ValidationTypes],
        ];

        foreach ($this->localeRepository->getActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['required', 'string'];
        }

        /**
         * Code and is_user_defined are immutable once an association type is created
         * (this holds for defaults as well as user-defined types), so they are only
         * validated/accepted on create.
         */
        if (! $this->route('id')) {
            $rules['code'] = [
                'required',
                new Code,
                new AssociationNotSupportedFields,
                Rule::unique('association_types', 'code')->ignore($this->route('id')),
            ];
        }

        return $rules;
    }
}
