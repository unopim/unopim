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
            'fields.*.validation' => ['sometimes', 'nullable', new ValidationTypes],
        ];

        /**
         * Code and is_user_defined are immutable once an association type is created
         * (this holds for defaults as well as user-defined types), so they are only
         * validated/accepted on create.
         *
         * `fields.*.code`/`fields.*.type` follow the same create-vs-update split: on
         * create every field entry must carry a valid code/type (there is no isNew/
         * isDelete keying on the create path, so a missing code/type would otherwise
         * be silently persisted as an empty string). On update, entries may omit
         * code/type when they represent a deletion payload, so the rule stays
         * relaxed to `sometimes`.
         */
        if (! $this->route('id')) {
            $rules['fields.*.code'] = ['required', new AssociationNotSupportedFields];
            $rules['fields.*.type'] = ['required', new AssociationFieldTypes];

            $rules['code'] = [
                'required',
                new Code,
                new AssociationNotSupportedFields,
                Rule::unique('association_types', 'code')->ignore($this->route('id')),
            ];
        } else {
            $rules['fields.*.code'] = ['sometimes', 'required', new AssociationNotSupportedFields];
            $rules['fields.*.type'] = ['sometimes', 'required', new AssociationFieldTypes];
        }

        foreach ($this->localeRepository->getActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['required', 'string'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $fields = (array) $this->input('fields', []);

            $seenCodes = [];

            foreach ($fields as $key => $field) {
                $field = (array) $field;

                if (filter_var($field['isDelete'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $code = $field['code'] ?? null;

                if (blank($code)) {
                    continue;
                }

                if (isset($seenCodes[$code])) {
                    $validator->errors()->add(
                        "fields.{$key}.code",
                        trans('admin::app.catalog.association_types.fields.same-code-error')
                    );

                    continue;
                }

                $seenCodes[$code] = true;
            }
        });
    }
}
