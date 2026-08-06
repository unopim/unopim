<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Contracts\VariantStructurePlanner as VariantStructurePlannerContract;
use Webkul\Product\Services\VariantStructurePlanner;
use Webkul\Product\Type\AbstractType;

class ProductRepository extends Repository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Product::class;
    }

    /**
     * Create product.
     *
     * @return Product
     */
    public function create(array $data)
    {
        $typeInstance = resolve(config('product_types.'.$data['type'].'.class'));

        return $typeInstance->create($data);
    }

    /**
     * Update product.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return Product
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = $this->findOrFail($id);

        $product = $product->getTypeInstance()->update($data, $id, $attribute);

        $product->refresh();

        if (isset($data['channels'])) {
            $product['channels'] = $data['channels'];
        }

        return $product;
    }

    /**
     * Update product with values key without processing further values
     */
    public function updateWithValues(array $data, int|string $id): Product
    {
        $product = $this->findOrFail($id);

        $product = $product->getTypeInstance()->updateWithValues($data, $id);

        $product->refresh();

        return $product;
    }

    /**
     * Updates the status of product.
     *
     * Dirty state is compared as booleans because PostgreSQL hydrates the
     * status column as true/false while MySQL hydrates 1/0.
     */
    public function updateStatus(bool $status, int $id): Product
    {
        $product = $this->findOrFail($id);

        $product->wasDirtyOnUpdate = ((bool) $product->status) !== $status;

        $product->status = (int) $status;

        if ($product->wasDirtyOnUpdate) {
            $product->save();
        }

        return $product;
    }

    /**
     * @param  array<int>  $productIds
     */
    public function massDelete(array $productIds): void
    {
        foreach ($productIds as $productId) {
            $product = $this->find($productId);

            if (! isset($product)) {
                continue;
            }

            Event::dispatch('catalog.product.delete.before', $productId);

            $this->delete($productId);

            Event::dispatch('catalog.product.delete.after', $productId);
        }
    }

    /**
     * @param  array<int>  $productIds
     */
    public function massUpdateStatus(array $productIds, bool $status): void
    {
        foreach ($productIds as $productId) {
            Event::dispatch('catalog.product.update.before', $productId);

            $product = $this->updateStatus($status, $productId);

            Event::dispatch('catalog.product.update.after', $product);
        }
    }

    /**
     * Copy product.
     *
     * @param  int  $id
     * @return Product
     */
    public function copy($id)
    {
        $product = $this->with([
            'attribute_family',
        ])->findOrFail($id);

        if ($product->parent_id) {
            throw new \Exception(trans('product::app.datagrid.variant-already-exist-message'));
        }

        return DB::transaction(fn () => $product->getTypeInstance()->copy());
    }

    /**
     * Checks variant configurable attributes uniqueness according to configurable product
     *
     * Each key of `$configAttributes` is concatenated into a `values->common->…` JSON
     * path, and the Postgres grammar wraps a path segment without escaping it, so a key
     * is rejected here unless it has the plain `^\w+$` attribute-code shape the core Code
     * rule enforces — before the query is built, so a hostile key never reaches the grammar.
     *
     * @throws \InvalidArgumentException
     */
    public function isUniqueVariantForProduct(string|int $productId, array $configAttributes, ?string $sku = null, string|int|null $variantId = '', ?string $type = null): bool
    {
        foreach (array_keys($configAttributes) as $variantAttribute) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', (string) $variantAttribute)) {
                throw new \InvalidArgumentException('Malformed variant attribute code supplied to the variant uniqueness check.');
            }
        }

        $query = $this->where('parent_id', $productId);

        if ($type !== null) {
            $query = $query->where('type', $type);
        }

        $query = $query->where(function ($subQuery) use ($configAttributes, $sku) {
            $subQuery->where(function ($attrQuery) use ($configAttributes) {
                foreach ($configAttributes as $variantAttribute => $value) {
                    $attrQuery->where('values->common->'.$variantAttribute, $value);
                }
            });

            if ($sku) {
                $subQuery->orWhere('sku', $sku);
            }
        });

        if (! in_array($variantId, ['', '0', 0], true)) {
            $query = $query->where('id', '<>', $variantId);
        }

        try {
            return $query->count() < 1;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Guard a variant-level write: reject a change to an ancestor-owned attribute,
     * allow an own-level one. An own-axis rename runs the duplicate check and the
     * persist closure in one locked transaction, so a concurrent rename cannot slip
     * between check and write. {@see AbstractType::update()} skips a root configurable.
     *
     * @return array<string, mixed>
     */
    public function guardVariantLevelWrite(Product $product, array $submittedCommon, ?\Closure $onGuarded = null, ?VariantStructurePlannerContract $planner = null): array
    {
        $planner ??= resolve(VariantStructurePlanner::class);

        $structure = $planner->structureFor($product);

        if (! $structure) {
            if ($onGuarded) {
                $onGuarded();
            }

            return $submittedCommon;
        }

        $resolved = $product->resolvedValues()['common'] ?? [];

        $ownAxisCodes = array_values(array_filter(
            $planner->allAxisCodes($structure),
            fn (string $code): bool => $planner->ownsAtOwnLevel($product, $code)
        ));

        $violations = [];
        $renamed = [];

        foreach ($submittedCommon as $code => $newValue) {
            if (! $planner->ownsAtOwnLevel($product, $code)) {
                $currentValue = $resolved[$code] ?? null;

                if (! $this->variantValueEquals($newValue, $currentValue)) {
                    $violations[] = $code;
                }

                continue;
            }

            if (in_array($code, $ownAxisCodes, true)) {
                $currentOwnValue = $product->values['common'][$code] ?? null;

                if (! $this->variantValueEquals($newValue, $currentOwnValue)) {
                    $renamed[$code] = $newValue;
                }
            }
        }

        if ($violations !== []) {
            throw ValidationException::withMessages([
                'immutable' => [trans('admin::app.catalog.products.immutable-fields', ['fields' => implode(', ', $violations)])],
            ]);
        }

        if ($renamed === [] || ! $product->parent_id) {
            if ($onGuarded) {
                $onGuarded();
            }

            return $submittedCommon;
        }

        $newTuple = array_merge(
            array_intersect_key($product->values['common'] ?? [], array_flip($ownAxisCodes)),
            $renamed
        );

        DB::transaction(function () use ($product, $newTuple, $onGuarded): void {
            $this->getModel()::query()->whereKey($product->parent_id)->lockForUpdate()->exists();

            if (! $this->isUniqueVariantForProduct($product->parent_id, $newTuple, null, $product->id, $product->type)) {
                throw ValidationException::withMessages([
                    'axis' => [trans('admin::app.catalog.products.edit.types.configurable.variant-given-exists', ['variants' => json_encode($newTuple)])],
                ]);
            }

            if ($onGuarded) {
                $onGuarded();
            }
        });

        return $submittedCommon;
    }

    /**
     * Compares two variant attribute values for equality, normalizing array values
     * (e.g. multiselect) via JSON encoding instead of a naive string cast, which
     * would otherwise collapse any two arrays to the literal string "Array".
     */
    private function variantValueEquals(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return json_encode($a) === json_encode($b);
        }

        return (string) $a === (string) $b;
    }

    /**
     * Retrieve product from slug without throwing an exception.
     *
     * @param  string  $slug
     * @return Product
     */
    public function findBySlug($slug)
    {
        return $this->findByAttributeCode('url_key', $slug);
    }

    /**
     * Retrieve product from slug.
     *
     * @param  string  $slug
     * @return Product
     */
    public function findBySlugOrFail($slug)
    {
        $product = $this->findBySlug($slug);

        if (! $product) {
            throw (new ModelNotFoundException)->setModel(
                $this->model::class, $slug
            );
        }

        return $product;
    }

    /**
     * Get all products.
     *
     * To Do (@devansh-webkul): Need to reduce all the request query from this repo and provide
     * good request parameter with an array type as an argument. Make a clean pull request for
     * this to have track record.
     *
     * @return Collection
     */
    public function getAll()
    {
        return $this->searchFromDatabase();
    }

    /**
     * Search product from database.
     *
     * @return Collection
     */
    public function searchFromDatabase()
    {
        $params = array_merge([
            'status'               => 1,
            'visible_individually' => 1,
            'url_key'              => null,
        ], request()->input());

        if (! empty($params['query'])) {
            $params['sku'] = $params['query'];
        }

        [$query] = $this->queryBuilderFromDatabase($params);

        $limit = $this->getPerPageLimit($params);

        return $query->paginate($limit);
    }

    public function queryBuilderFromDatabase($params): array
    {
        $query = $this->with([
            'attribute_family',
            'parent',
            'super_attributes',
            'variants',
        ])->scopeQuery(function ($query) use ($params) {
            $qb = $query->select('products.*');

            if (! empty($params['type'])) {
                $qb->where('products.type', $params['type']);
            }

            if (! empty($params['sku'])) {
                $qb->where('products.sku', 'like', '%'.$params['sku'].'%');
            }

            if (! empty($params['ids'])) {
                $qb->whereIn('products.id', array_filter((array) $params['ids'], 'is_numeric'));
            }

            if (! empty($params['skipSku'])) {
                $qb->whereNotIn('products.sku', is_string($params['skipSku']) ? [$params['skipSku']] : $params['skipSku']);
            }

            return $qb;
        });

        return [$query];
    }

    /**
     * Fetch per page limit from toolbar helper. Adapter for this repository.
     */
    public function getPerPageLimit(array $params): int
    {
        return product_toolbar()->getLimit($params);
    }

    /**
     * Fetch sort option from toolbar helper. Adapter for this repository.
     */
    public function getSortOptions(array $params): array
    {
        return product_toolbar()->getOrder($params);
    }

    /**
     * Returns product's super attribute with options.
     *
     * @param  Product  $product
     */
    public function getSuperAttributes($product): array
    {
        $superAttributes = [];

        foreach ($product->super_attributes as $key => $attribute) {
            $superAttributes[$key] = $attribute->toArray();

            foreach ($attribute->options as $option) {
                $superAttributes[$key]['options'][] = [
                    'id'           => $option->id,
                    'admin_name'   => $option->admin_name,
                    'sort_order'   => $option->sort_order,
                    'swatch_value' => $option->swatch_value,
                ];
            }
        }

        return $superAttributes;
    }
}
