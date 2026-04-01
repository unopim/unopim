<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Sku;
use Webkul\Product\Repositories\ProductRepository;

class ProductForm extends FormRequest
{
    /**
     * Rules.
     *
     * @var array
     */
    protected $rules;

    /**
     * Create a new form request instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    /**
     * Determine if the product is authorized to make this request.
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
        $product = $this->productRepository->find($this->id);

        $this->rules = $product->getTypeInstance()->getTypeValidationRules();

        $this->rules['sku'] = ['required', 'unique:products,sku,'.$this->id, new Sku];
        $this->rules['status'] = ['boolean'];

        return $this->rules;
    }

    public function prepareForValidation()
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
