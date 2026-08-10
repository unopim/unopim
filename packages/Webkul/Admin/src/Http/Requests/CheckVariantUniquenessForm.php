<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

class CheckVariantUniquenessForm extends FormRequest
{
    /**
     * Create a new form request instance.
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected VariantStructurePlanner $variantStructurePlanner,
    ) {}

    /**
     * Determine if the request is authorized to probe variant uniqueness.
     *
     * Mirrors the `catalog.products` key the route is mapped to in
     * `packages/Webkul/Admin/src/Config/acl.php`, so the endpoint stays gated
     * even if it is ever reached outside the `admin` middleware group.
     */
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.products'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * `variantAttributes` keys reach a `values->common->…` JSON path that the Postgres
     * grammar wraps unescaped, so an unknown key is rejected rather than dropped: only
     * an axis code the parent's structure declares, or one of a structureless
     * configurable's super attributes, may reach the query builder.
     */
    public function rules(): array
    {
        $axisCodes = $this->allowedAxisCodes();

        return [
            'parentId'  => ['required', 'integer', 'min:1'],
            'sku'       => ['nullable', 'string', 'max:191'],
            'variantId' => ['nullable', 'integer', 'min:1'],

            'variantAttributes' => ['required', 'array', function (string $attribute, mixed $value, callable $fail) use ($axisCodes): void {
                $unknown = array_diff(array_map('strval', array_keys((array) $value)), $axisCodes);

                if ($unknown) {
                    $fail(trans('validation.in', ['attribute' => implode(', ', $unknown)]));
                }
            }],

            'variantAttributes.*' => ['nullable', 'string', 'max:191'],
        ];
    }

    /**
     * The axis attribute codes the requested parent may legitimately be probed on.
     *
     * An unresolvable parent yields an empty allowlist, so every submitted key
     * fails and the endpoint cannot be used to probe an arbitrary product.
     *
     * @return array<int, string>
     */
    protected function allowedAxisCodes(): array
    {
        $parent = $this->parentProduct();

        if (! $parent) {
            return [];
        }

        $structure = $this->variantStructurePlanner->structureFor($parent);

        return array_values(array_unique(array_merge(
            $structure ? $this->variantStructurePlanner->allAxisCodes($structure) : [],
            $parent->super_attributes->pluck('code')->all(),
        )));
    }

    /**
     * The configurable the submitted axis values are checked against.
     */
    protected function parentProduct(): ?Product
    {
        $parentId = $this->input('parentId');

        if (! is_numeric($parentId) || (int) $parentId < 1) {
            return null;
        }

        return $this->productRepository->find((int) $parentId);
    }
}
