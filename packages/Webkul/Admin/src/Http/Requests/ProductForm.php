<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Sku;
use Webkul\Product\Contracts\Product;
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

        $this->rules['sku'] = ['required', new Sku];
        $this->rules['status'] = ['boolean'];

        return $this->rules;
    }

    /**
     * Configure the validator instance.
     *
     * The uniqueness of the SKU is checked here (instead of a plain `unique` rule) so
     * the failure can name the conflicting product and be surfaced on the visible SKU
     * field rather than the hidden `sku` input, where the error was previously swallowed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $errors = $validator->errors();

            /**
             * Re-key the flat `sku` errors (required/format) onto the visible
             * `values[common][sku]` field so they are actually rendered on screen.
             */
            foreach ($errors->get('sku') as $message) {
                $errors->add('values[common][sku]', $message);
            }

            $errors->forget('sku');

            $sku = $this->input('sku');

            if (empty($sku)) {
                return;
            }

            $existing = $this->productRepository->findOneByField('sku', $sku);

            if ($existing && (int) $existing->id !== (int) $this->id) {
                $errors->add('values[common][sku]', trans('admin::app.catalog.products.sku-already-used', [
                    'id'   => $existing->id,
                    'name' => $this->resolveProductName($existing),
                ]));
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * Flash the standard failure notification so the user gets a visible toast on save
     * (matching the behaviour of the in-controller value validation), in addition to the
     * field level error that is scrolled into focus.
     */
    protected function failedValidation(Validator $validator)
    {
        session()->flash('error', trans('admin::app.catalog.products.update-failure'));

        parent::failedValidation($validator);
    }

    /**
     * Resolve a human readable product name from the scoped values for the current
     * channel and locale, falling back to the SKU when no name is available.
     */
    protected function resolveProductName(Product $product): string
    {
        $values = $product->values ?? [];

        $channel = core()->getRequestedChannelCode();
        $locale = core()->getRequestedLocaleCode();

        return data_get($values, "channel_locale_specific.$channel.$locale.name")
            ?? data_get($values, "locale_specific.$locale.name")
            ?? data_get($values, 'common.name')
            ?? $product->sku;
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
