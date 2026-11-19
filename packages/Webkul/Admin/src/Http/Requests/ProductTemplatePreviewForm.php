<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;

class ProductTemplatePreviewForm extends FormRequest
{
    /**
     * Determine if the user is authorized to preview product templates.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.products.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:255', 'exists:attributes,code'],
            'values'    => ['nullable', 'array'],

            'values.'.AbstractType::COMMON_VALUES_KEY         => ['nullable', 'array'],
            'values.'.AbstractType::LOCALE_VALUES_KEY         => ['nullable', 'array'],
            'values.'.AbstractType::CHANNEL_VALUES_KEY        => ['nullable', 'array'],
            'values.'.AbstractType::CHANNEL_LOCALE_VALUES_KEY => ['nullable', 'array'],
        ];
    }

    /**
     * Restrict the previewed attribute to the templated attributes of the product family.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = app(ProductRepository::class)->find((int) $this->route('id'));

            if (! $product) {
                return;
            }

            $code = (string) $this->input('attribute');

            $attribute = app(AttributeRepository::class)->findOneByField('code', $code);

            $inFamily = (bool) $product->attribute_family?->customAttributes()->where('attributes.code', $code)->exists();

            if (! $inFamily || ! $attribute?->canHoldTemplate()) {
                $validator->errors()->add('attribute', trans('validation.exists', ['attribute' => 'attribute']));
            }
        });
    }
}
