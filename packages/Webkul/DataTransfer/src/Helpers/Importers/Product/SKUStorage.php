<?php

namespace Webkul\DataTransfer\Helpers\Importers\Product;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Repositories\ProductRepository;

class SKUStorage
{
    /**
     * Delimiter for SKU information
     */
    private const string DELIMITER = '|';

    /**
     * Items contains SKU as key and product information as value
     */
    protected array $items = [];

    /**
     * Columns which will be selected from database
     */
    protected array $selectColumns = [
        'id',
        'type',
        'sku',
        'attribute_family_id',
    ];

    /**
     * Create a new helper instance.
     */
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the SKU
     *
     * Uses raw DB queries and chunked processing for maximum performance.
     */
    public function load(array $skus = []): void
    {
        if ($skus === []) {
            $products = DB::table('products')
                ->select($this->selectColumns)
                ->get();

            foreach ($products as $product) {
                $this->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);
            }

            return;
        }

        /**
         * Filter out already loaded SKUs to avoid redundant DB queries.
         */
        $skusToLoad = array_filter($skus, fn (string $sku) => ! $this->has($sku));

        if ($skusToLoad === []) {
            return;
        }

        /**
         * Chunk large SKU lists to prevent query parameter overflow
         * and reduce memory pressure. Uses raw DB query instead of
         * Eloquent to avoid model hydration overhead.
         */
        foreach (array_chunk($skusToLoad, 1000) as $chunk) {
            $products = DB::table('products')
                ->select($this->selectColumns)
                ->whereIn('sku', $chunk)
                ->get();

            foreach ($products as $product) {
                $this->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);
            }
        }
    }

    /**
     * Get SKU information
     */
    public function set(string $sku, array $data): self
    {
        $this->items[$sku] = implode(self::DELIMITER, [
            $data['id'],
            $data['type'],
            $data['attribute_family_id'],
        ]);

        return $this;
    }

    /**
     * Check if SKU exists
     */
    public function has(string $sku): bool
    {
        return isset($this->items[$sku]);
    }

    /**
     * Get SKU information
     */
    public function get(string $sku): ?array
    {
        if (! $this->has($sku)) {
            return null;
        }

        $data = explode(self::DELIMITER, (string) $this->items[$sku]);

        return [
            'id'                  => $data[0],
            'type'                => $data[1],
            'attribute_family_id' => $data[2],
        ];
    }

    /**
     * Return SKU filtered by product type
     */
    public function getByType(string $type): ?array
    {
        return Arr::where($this->items, fn (string $row, string $key) => str_contains($row, '|'.$type.'|'));
    }

    /**
     * Is storage is empty
     */
    public function isEmpty(): int
    {
        return $this->items === [];
    }

    /**
     * Get all items in a normalized format with sku as key
     */
    public function getItems(): array
    {
        $allItems = [];

        foreach ($this->items as $key => $item) {
            $data = explode(self::DELIMITER, (string) $item);

            $allItems[$key] = [
                'id'                  => $data[0],
                'type'                => $data[1],
                'attribute_family_id' => $data[2],
            ];
        }

        return $allItems;
    }
}
