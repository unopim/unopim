<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\Product;

class ProductRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected ElasticSearchRepository $elasticSearchRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Product\Contracts\Product';
    }

    /**
     * Create product.
     *
     * @return \Webkul\Product\Contracts\Product
     */
    public function create(array $data)
    {
        $typeInstance = app(config('product_types.'.$data['type'].'.class'));

        $product = $typeInstance->create($data);

        return $product;
    }

    /**
     * Update product.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Product\Contracts\Product
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
     */
    public function updateStatus(bool $status, int $id): Product
    {
        $product = $this->findOrFail($id);

        $product->status = (int) $status;

        $product->save();

        return $product;
    }

    /**
     * Copy product.
     *
     * @param  int  $id
     * @return \Webkul\Product\Contracts\Product
     */
    public function copy($id)
    {
        $product = $this->with([
            'attribute_family',
        ])->findOrFail($id);

        if ($product->parent_id) {
            throw new \Exception(trans('product::app.datagrid.variant-already-exist-message'));
        }

        return DB::transaction(function () use ($product) {
            $copiedProduct = $product->getTypeInstance()->copy();

            return $copiedProduct;
        });
    }

    /**
     * Checks variant configurable attributes uniqueness according to configurable product
     */
    public function isUniqueVariantForProduct(string|int $productId, array $configAttributes, ?string $sku = null, string|int|null $variantId = ''): bool
    {
        $query = $this->where('parent_id', $productId);

        foreach ($configAttributes as $variantAttribute => $value) {
            $query = $query->where('values->common->'.$variantAttribute, $value);
        }

        if (! empty($variantId)) {
            $query = $query->where('id', '<>', $variantId);
        }

        if ($sku) {
            $query = $query->orWhere('sku', $sku);

            if (! empty($variantId)) {
                $query = $query->where('id', '<>', $variantId);
            }
        }

        try {
            return $query->count() < 1;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Retrieve product from slug without throwing an exception.
     *
     * @param  string  $slug
     * @return \Webkul\Product\Contracts\Product
     */
    public function findBySlug($slug)
    {
        if (core()->getConfigData('catalog.products.storefront.search_mode') == 'elastic') {
            request()->query->add(['url_key' => $slug]);

            $indices = $this->elasticSearchRepository->search(null, [
                'type'  => '',
                'from'  => 0,
                'limit' => 1,
                'sort'  => 'id',
                'order' => 'desc',
            ]);

            return $this->find(current($indices['ids']));
        }

        return $this->findByAttributeCode('url_key', $slug);
    }

    /**
     * Retrieve product from slug.
     *
     * @param  string  $slug
     * @return \Webkul\Product\Contracts\Product
     */
    public function findBySlugOrFail($slug)
    {
        $product = $this->findBySlug($slug);

        if (! $product) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->model), $slug
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
     * @return \Illuminate\Support\Collection
     */
    public function getAll()
    {
        if (core()->getConfigData('catalog.products.storefront.search_mode') == 'elastic') {
            return $this->searchFromElastic();
        }

        return $this->searchFromDatabase();
    }

    /**
     * Search product from database.
     *
     * @return \Illuminate\Support\Collection
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

    public function queryBuilderFromDatabase($params)
    {
        $query = $this->with([
            'attribute_family',
            'parent',
            'super_attributes',
            'variants',
        ])->scopeQuery(function ($query) use ($params) {
            $prefix = DB::getTablePrefix();

            $qb = $query->distinct()
                ->select('products.*')
                ->leftJoin('products as variants', DB::raw('COALESCE('.$prefix.'variants.parent_id, '.$prefix.'variants.id)'), '=', 'products.id');

            if (! empty($params['type'])) {
                $qb->where('products.type', $params['type']);
            }

            if (! empty($params['sku'])) {
                $qb->where('products.sku', 'like', '%'.$params['sku'].'%');
            }

            if (! empty($params['skipSku'])) {
                $qb->whereNotIn('products.sku', is_string($params['skipSku']) ? [$params['skipSku']] : $params['skipSku']);
            }

            return $qb->groupBy('products.id');
        });

        return [$query];
    }

    /**
     * Search product from elastic search.
     *
     * To Do (@devansh-webkul): Need to reduce all the request query from this repo and provide
     * good request parameter with an array type as an argument. Make a clean pull request for
     * this to have track record.
     *
     * @return \Illuminate\Support\Collection
     */
    public function searchFromElastic()
    {
        $params = request()->input();

        [$query, $indices, $limit, $currentPage] = $this->queryBuilderFromElastic($params);

        $items = $indices['total'] ? $query->get() : [];

        $results = new LengthAwarePaginator($items, $indices['total'], $limit, $currentPage, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]);

        return $results;
    }

    public function queryBuilderFromElastic($params)
    {
        $currentPage = Paginator::resolveCurrentPage('page');

        $limit = $this->getPerPageLimit($params);

        $sortOptions = $this->getSortOptions($params);

        $indices = $this->elasticSearchRepository->search($params['category_id'] ?? null, [
            'type'  => $params['type'] ?? '',
            'from'  => ($currentPage * $limit) - $limit,
            'limit' => $limit,
            'sort'  => $sortOptions['sort'],
            'order' => $sortOptions['order'],
        ]);

        $query = $this->with([
            'attribute_family',
            'parent',
            'super_attributes',
        ])->scopeQuery(function ($query) use ($indices) {
            $qb = $query->distinct()
                ->whereIn('products.id', $indices['ids']);

            // Sort collection
            $qb->orderBy(DB::raw('FIELD(id, '.implode(',', $indices['ids']).')'));

            return $qb;
        });

        return [$query, $indices, $limit, $currentPage];
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
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return \Illuminate\Support\Collection
     */
    public function getSuperAttributes($product)
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
