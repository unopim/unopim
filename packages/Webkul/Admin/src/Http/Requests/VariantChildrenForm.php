<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Repositories\ProductRepository;

class VariantChildrenForm extends FormRequest
{
    /**
     * Create a new form request instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected VariantStructurePlanner $variantStructurePlanner,
    ) {}

    /**
     * Determine if the request is authorized to browse a configurable's
     * created variant children.
     *
     * @return bool
     */
    public function authorize()
    {
        abort_unless(bouncer()->hasPermission('catalog.products.edit'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The axes a listing labels by are derived from the resolved parent's
     * level, not from the client — `axis` is accepted only for backwards
     * compatibility and is still restricted to the configurable's own axis
     * codes so it can never be used to probe unrelated data.
     *
     * @return array
     */
    public function rules()
    {
        $configurable = $this->productRepository->find($this->route('configurableId'));

        abort_if(! $configurable || $configurable->type !== 'configurable' || ! $configurable->variantStructure, 404);

        $axisCodes = $this->variantStructurePlanner->allAxisCodes($configurable->variantStructure);

        return [
            'parent_id' => ['nullable', 'integer'],
            'axis'      => ['nullable', 'string', Rule::in($axisCodes)],
            'query'     => ['nullable', 'string'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'perPage'   => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
