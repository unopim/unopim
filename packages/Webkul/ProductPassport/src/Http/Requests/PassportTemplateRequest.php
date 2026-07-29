<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\ProductPassport\Enums\PassportFieldRole;
use Webkul\ProductPassport\Enums\PassportFieldSource;
use Webkul\ProductPassport\Enums\PassportFieldTier;
use Webkul\ProductPassport\Models\PassportTemplateFamilyProxy;

class PassportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission(
            $this->templateId() === null
                ? 'catalog.passport.template.create'
                : 'catalog.passport.template.edit'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $templateId = $this->templateId();

        $rules = [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z]+[a-zA-Z0-9_]+$/',
                Rule::unique('passport_templates', 'code')->ignore($templateId),
            ],
            'is_enabled'            => ['nullable', 'boolean'],
            'families'              => ['nullable', 'array'],
            'families.*'            => ['integer', 'exists:attribute_families,id'],
            'sections'              => ['nullable', 'array'],
            'sections.*.code'       => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+[a-zA-Z0-9_]+$/'],
            'fields'                => ['nullable', 'array'],
            'fields.*.code'         => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+[a-zA-Z0-9_]+$/'],
            'fields.*.source_type'  => ['required', Rule::enum(PassportFieldSource::class)],
            'fields.*.attribute_id' => ['nullable', 'integer', 'exists:attributes,id'],
            'fields.*.tier'         => ['required', Rule::enum(PassportFieldTier::class)],
            'fields.*.is_required'  => ['nullable', 'boolean'],
            'fields.*.role'         => ['nullable', Rule::enum(PassportFieldRole::class)],
            'fields.*.section'      => ['nullable', 'string'],
        ];

        foreach (app(LocaleRepository::class)->getActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['nullable', 'string', 'max:255'];
            $rules['sections.*.'.$locale->code.'.name'] = ['nullable', 'string', 'max:255'];
            $rules['fields.*.'.$locale->code.'.label'] = ['nullable', 'string', 'max:255'];
            $rules['fields.*.'.$locale->code.'.fixed_value'] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * Blank rows the merchant appended but never filled are dropped rather than
     * rejected, and checkbox-style flags arrive as strings.
     */
    protected function prepareForValidation(): void
    {
        $fields = array_values(array_filter(
            (array) $this->input('fields', []),
            fn ($field): bool => is_array($field) && trim((string) ($field['code'] ?? '')) !== '',
        ));

        $sections = array_values(array_filter(
            (array) $this->input('sections', []),
            fn ($section): bool => is_array($section) && trim((string) ($section['code'] ?? '')) !== '',
        ));

        $this->merge([
            'fields'   => $fields,
            'sections' => $sections,
            'families' => $this->familyIds(),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectDuplicates($validator);

            $this->rejectMissingAttributeSource($validator);

            $this->rejectUnknownSection($validator);

            $this->rejectForeignFamilies($validator);
        });
    }

    /**
     * A field's role is a reserved payload slot, and a code identifies the field
     * in the payload — both must be unique inside one template.
     */
    private function rejectDuplicates(Validator $validator): void
    {
        $seenCodes = [];
        $seenRoles = [];

        foreach ((array) $this->input('fields', []) as $index => $field) {
            $code = (string) ($field['code'] ?? '');

            if (isset($seenCodes[$code])) {
                $validator->errors()->add('fields.'.$index.'.code', trans('passport::app.templates.errors.duplicate-code'));
            }

            $seenCodes[$code] = true;

            $role = (string) ($field['role'] ?? '');

            if ($role === '') {
                continue;
            }

            if (isset($seenRoles[$role])) {
                $validator->errors()->add('fields.'.$index.'.role', trans('passport::app.templates.errors.duplicate-role'));
            }

            $seenRoles[$role] = true;
        }

        $seenSections = [];

        foreach ((array) $this->input('sections', []) as $index => $section) {
            $code = (string) ($section['code'] ?? '');

            if (isset($seenSections[$code])) {
                $validator->errors()->add('sections.'.$index.'.code', trans('passport::app.templates.errors.duplicate-code'));
            }

            $seenSections[$code] = true;
        }
    }

    /**
     * An attribute-sourced field without an attribute would publish nothing, so
     * it is rejected at save time instead of silently disappearing.
     */
    private function rejectMissingAttributeSource(Validator $validator): void
    {
        foreach ((array) $this->input('fields', []) as $index => $field) {
            if (($field['source_type'] ?? '') !== PassportFieldSource::Attribute->value) {
                continue;
            }

            if (empty($field['attribute_id'])) {
                $validator->errors()->add('fields.'.$index.'.attribute_id', trans('passport::app.templates.errors.attribute-required'));
            }
        }
    }

    private function rejectUnknownSection(Validator $validator): void
    {
        $sectionCodes = array_column((array) $this->input('sections', []), 'code');

        foreach ((array) $this->input('fields', []) as $index => $field) {
            $section = (string) ($field['section'] ?? '');

            if ($section === '' || in_array($section, $sectionCodes, true)) {
                continue;
            }

            $validator->errors()->add('fields.'.$index.'.section', trans('passport::app.templates.errors.unknown-section'));
        }
    }

    /**
     * A family resolves to one template, so a family already claimed elsewhere
     * is rejected with the claiming template named rather than failing on the
     * unique index.
     */
    private function rejectForeignFamilies(Validator $validator): void
    {
        $families = array_map('intval', (array) $this->input('families', []));

        if ($families === []) {
            return;
        }

        $claimed = PassportTemplateFamilyProxy::modelClass()::query()
            ->whereIn('attribute_family_id', $families)
            ->when($this->templateId() !== null, fn ($query) => $query->where('passport_template_id', '!=', $this->templateId()))
            ->with(['family', 'template.translations'])
            ->get();

        foreach ($claimed as $row) {
            $validator->errors()->add('families', trans('passport::app.templates.errors.family-claimed', [
                'family'   => $row->family?->code ?? $row->attribute_family_id,
                'template' => $row->template?->name ?? $row->template?->code ?? '',
            ]));
        }
    }

    /**
     * The admin multiselect posts its selection as one comma separated value, so
     * both that shape and a plain array of ids are accepted.
     *
     * @return list<int>
     */
    private function familyIds(): array
    {
        $families = $this->input('families', []);

        if (is_string($families)) {
            $families = explode(',', $families);
        }

        return array_values(array_filter(array_map(
            fn ($id): int => (int) trim((string) $id),
            (array) $families,
        )));
    }

    private function templateId(): ?int
    {
        $id = $this->route('id');

        return $id === null ? null : (int) $id;
    }
}
