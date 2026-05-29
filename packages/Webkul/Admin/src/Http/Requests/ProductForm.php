<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Sku;
use Webkul\Product\Repositories\ProductRepository;

class ProductForm extends FormRequest
{
    /**
     * Rules.
     */
    protected array $rules;

    /**
     * Create a new form request instance.
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    /**
     * Determine if the product is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $product = $this->productRepository->find($this->id);

        $this->rules = $product->getTypeInstance()->getTypeValidationRules();

        $this->rules['sku'] = ['required', 'unique:products,sku,'.$this->id, new Sku];
        $this->rules['status'] = ['boolean'];

        return $this->rules;
    }

    #[\Override]
    public function prepareForValidation(): void
    {
        if (isset($this->uniqueFields['values.common.sku']) || isset($this->values['common']['sku'])) {
            $this->merge([
                'sku' => $this->values['common']['sku'],
            ]);
        }

        $this->merge([
            'status' => (int) $this->status,
        ]);
    }
}
