<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Rules\Code;

class AttributeOptionForm extends FormRequest
{
    public const SVG_MIME_TYPES = [
        'image/svg',
        'image/svg+xml',
    ];

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $attributeId = $this->route('attribute_id');

        $rules = [
            'code' => [
                'required',
                Rule::unique('attribute_options', 'code')->where(function ($query) use ($attributeId) {
                    return $query->where('attribute_id', $attributeId);
                }),
                new Code,
            ],
            'locales.*.label' => 'nullable|string',
        ];

        if ($this->isImageSwatch($attributeId)) {
            $rules['swatch_value'] = [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif,webp,bmp,svg',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/svg,image/svg+xml',
            ];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $swatchValue = $this->file('swatch_value');

            if (! $swatchValue instanceof UploadedFile) {
                return;
            }

            if (! in_array($swatchValue->getMimeType(), self::SVG_MIME_TYPES, true)) {
                return;
            }

            $contents = (string) file_get_contents($swatchValue->getRealPath());

            if (preg_match('/<script\b/i', $contents) || preg_match('/\son\w+\s*=/i', $contents)) {
                $validator->errors()->add(
                    'swatch_value',
                    trans('admin::app.catalog.attributes.edit.option.invalid-swatch-image')
                );
            }
        });
    }

    protected function isImageSwatch($attributeId): bool
    {
        if (blank($attributeId)) {
            return false;
        }

        $attribute = app(AttributeRepository::class)->find($attributeId);

        return $attribute && $attribute->swatch_type === 'image';
    }
}
