<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

class ConfigurableVariantsForm extends FormRequest
{
    /**
     * The configurable product resolved from the route.
     */
    protected ?Product $configurable = null;

    /**
     * Create a new form request instance.
     */
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Determine if the request is authorized to browse a configurable's variants.
     */
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.products.edit'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `filters` keys reach a `values->common->…` JSON path, so only the
     * configurable's own super attribute codes are accepted.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $superAttributeCodes = $this->configurable()->super_attributes->pluck('code')->all();

        return [
            'query'     => ['nullable', 'string', 'max:191'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'perPage'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'filters'   => ['nullable', 'array:'.implode(',', $superAttributeCodes)],
            'filters.*' => ['nullable', 'string', 'max:191'],
            'exclude'   => ['nullable', 'array', 'max:1000'],
            'exclude.*' => ['integer'],
        ];
    }

    /**
     * The configurable product whose variants are listed.
     */
    public function configurable(): Product
    {
        if ($this->configurable) {
            return $this->configurable;
        }

        $product = $this->productRepository->find($this->route('id'));

        abort_if(! $product || $product->type !== ProductType::Configurable->value, 404);

        return $this->configurable = $product;
    }
}
