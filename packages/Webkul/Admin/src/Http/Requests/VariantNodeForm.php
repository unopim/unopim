<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\Sku;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Repositories\ProductRepository;

class VariantNodeForm extends FormRequest
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
     * Determine if the request is authorized to create a variant node.
     *
     * @return bool
     */
    public function authorize()
    {
        abort_unless(bouncer()->hasPermission('catalog.products.create'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A level may split on several axes (color + size + brand), so a node
     * carries a `values` map. Every axis of the target level is required and
     * no other key is accepted, which keeps an arbitrary attribute code — or
     * an option belonging to another attribute — out of a node's fixed axis
     * values.
     *
     * @return array
     */
    public function rules()
    {
        $configurable = $this->productRepository->find($this->route('configurableId'));

        abort_if(! $configurable || $configurable->type !== 'configurable' || ! $configurable->variantStructure, 404);

        $structure = $configurable->variantStructure;

        $axes = $structure->axes
            ->filter(fn ($axis): bool => $axis->level === $this->axisLevel($structure))
            ->sortBy('position');

        $axisCodes = $axes->map(fn ($axis) => $axis->attribute->code)->all();

        $rules = [
            'parent_id' => ['nullable', 'integer'],
            'role'      => ['required', 'string', Rule::in(['variant_group', 'simple'])],
            'values'    => ['required', 'array', function (string $attribute, mixed $value, callable $fail) use ($axisCodes) {
                $unknown = array_diff(array_keys((array) $value), $axisCodes);

                if ($unknown) {
                    $fail(trans('validation.in', ['attribute' => implode(', ', $unknown)]));
                }
            }],
            'sku' => ['nullable', 'string', 'unique:products,sku', new Sku],
        ];

        foreach ($axes as $axis) {
            $rules['values.'.$axis->attribute->code] = [
                'required',
                'string',
                Rule::exists('attribute_options', 'code')->where('attribute_id', $axis->attribute_id),
            ];
        }

        return $rules;
    }

    /**
     * The structure level the new node splits on: a `variant_group` is always a
     * level 1 node; a `simple` node is the leaf — level 2 when the structure
     * has one, level 1 otherwise.
     */
    protected function axisLevel(VariantStructure $structure): string
    {
        if ($this->input('role') === 'variant_group') {
            return 'level_1';
        }

        return (int) $structure->levels === 2 ? 'level_2' : 'level_1';
    }
}
