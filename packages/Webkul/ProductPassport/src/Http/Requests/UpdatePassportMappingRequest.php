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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mapping'   => ['nullable', 'array'],
            'mapping.*' => ['nullable', 'string', 'exists:attributes,code'],
        ];
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
        });
    }

    private function isDocument(Attribute $attribute): bool
    {
        return in_array($attribute->type, self::DOCUMENT_TYPES, true);
    }
}
