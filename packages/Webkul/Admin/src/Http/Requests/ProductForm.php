<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Rules\Slug;
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

        if ($this->uniqueFields) {
            foreach ($this->uniqueFields as $fieldName => $fieldNamespace) {
                $pathToValue = str_replace(['values', '[', '][', ']'], ['', '->', '->', ''], $fieldNamespace);

                $this->rules[$fieldName] = 'unique:products,values'.$pathToValue;

                if ($this->id) {
                    $this->rules[$fieldName] = $this->rules[$fieldName].",{$this->id}";
                }
            }
        }

        $this->rules['sku'] = ['required', 'unique:products,sku,'.$this->id, new Slug];

        return $this->rules;
    }

    public function prepareForValidation()
    {
        if (isset($this->uniqueFields['values.common.sku']) || isset($this->values['common']['sku'])) {
            $this->merge([
                'sku' => $this->values['common']['sku'],
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        try {
            parent::failedValidation($validator);
        } catch (ValidationException $e) {
            $messages = [];

            $translator = $validator->getTranslator();

            foreach ($validator->errors()->messages() as $key => $message) {
                if (
                    is_string($key)
                    && str_contains($key, 'values')
                    && isset($this->uniqueFields[$key])
                ) {
                    $messages[$this->uniqueFields[$key]] = $translator->get('admin::app.catalog.products.unique-validation');
                }

                $messages[$key] = $message;
            }

            $e = $e::withMessages($messages);

            if (count($messages) == 1 && isset($messages['sku'][0])) {
                session()->flash('error', $messages['sku'][0]);
            }

            throw $e;
        }
    }
}
