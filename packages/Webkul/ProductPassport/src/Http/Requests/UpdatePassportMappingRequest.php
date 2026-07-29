<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeProxy;

class UpdatePassportMappingRequest extends FormRequest
{
    /**
     * A file/image passport field carries a document; every other type carries
     * a value. A source may only feed a field of its own class — kept in sync
     * with `PassportMappingController::DOCUMENT_TYPES`.
     */
    private const DOCUMENT_TYPES = ['file', 'image'];

    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.mapping');
    }

    /**
     * Each mapping value is an optional source-attribute code; an unknown code
     * is rejected so a crafted request can never point a passport field at a
     * non-existent attribute. The keys are the passport field codes.
     *
     * Custom fields are the merchant's own rows: a user-typed label plus the
     * source attribute code it publishes from. The label is free-form data
     * (escaped on output, not localized); the attribute must exist so a crafted
     * request can never surface a non-existent attribute on the public page.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mapping'                   => ['nullable', 'array'],
            'mapping.*'                 => ['nullable', 'string', 'exists:attributes,code'],
            'custom_fields'             => ['nullable', 'array'],
            'custom_fields.*.name'      => ['required', 'string', 'max:255'],
            'custom_fields.*.attribute' => ['required', 'string', 'exists:attributes,code'],
        ];
    }

    /**
     * Drop rows the merchant added but left entirely blank, so an empty
     * appended row never fails validation; a half-filled row still errors.
     */
    protected function prepareForValidation(): void
    {
        $rows = $this->input('custom_fields');

        if (! is_array($rows)) {
            return;
        }

        $this->merge([
            'custom_fields' => array_values(array_filter(
                $rows,
                fn ($row): bool => is_array($row)
                    && (trim((string) ($row['name'] ?? '')) !== '' || trim((string) ($row['attribute'] ?? '')) !== ''),
            )),
        ]);
    }

    /**
     * Fails closed on a cross-class mapping: a document field pointed at a
     * value source (or vice versa) would either publish nothing or surface the
     * wrong shape, so it is rejected server-side regardless of what the
     * type-filtered screen offered.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('mapping', []) as $field => $source) {
                if (empty($source)) {
                    continue;
                }

                $fieldAttribute = AttributeProxy::modelClass()::query()->where('code', $field)->first();

                $sourceAttribute = AttributeProxy::modelClass()::query()->where('code', $source)->first();

                if (! $fieldAttribute instanceof Attribute || ! $sourceAttribute instanceof Attribute) {
                    continue;
                }

                if ($this->isDocument($fieldAttribute) !== $this->isDocument($sourceAttribute)) {
                    $validator->errors()->add('mapping.'.$field, trans('passport::app.mapping.type-mismatch'));
                }
            }

            $this->rejectMediaCustomFieldSources($validator);
        });
    }

    /**
     * A custom field always publishes its source's value into the consumer
     * section as plain text; a file/image source has only a storage path, which
     * would render as a broken raw path. Regulatory file fields already flow
     * through the dpp document pipeline, so a media source here is always wrong
     * and is rejected regardless of what the type-filtered screen offered.
     */
    private function rejectMediaCustomFieldSources(Validator $validator): void
    {
        $rows = (array) $this->input('custom_fields', []);

        $codes = array_values(array_filter(array_map(
            fn ($row): string => is_array($row) ? (string) ($row['attribute'] ?? '') : '',
            $rows,
        )));

        if ($codes === []) {
            return;
        }

        $mediaCodes = AttributeProxy::modelClass()::query()
            ->whereIn('code', $codes)
            ->whereIn('type', self::DOCUMENT_TYPES)
            ->pluck('code')
            ->all();

        if ($mediaCodes === []) {
            return;
        }

        foreach ($rows as $index => $row) {
            $code = is_array($row) ? (string) ($row['attribute'] ?? '') : '';

            if (in_array($code, $mediaCodes, true)) {
                $validator->errors()->add('custom_fields.'.$index.'.attribute', trans('passport::app.mapping.custom-media-source'));
            }
        }
    }

    private function isDocument(Attribute $attribute): bool
    {
        return in_array($attribute->type, self::DOCUMENT_TYPES, true);
    }
}
