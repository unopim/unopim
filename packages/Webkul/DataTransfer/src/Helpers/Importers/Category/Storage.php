<?php

namespace Webkul\DataTransfer\Helpers\Importers\Category;

use Webkul\Category\Repositories\CategoryRepository;

class Storage
{
    /**
     * Items contains name as key and product information as value
     */
    protected array $items = [];

    /**
     * Columns which will be selected from database
     */
    protected array $selectColumns = [
        'id',
        'code',
    ];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected CategoryRepository $categoryRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the Categories
     */
    public function load(array $codes = []): void
    {
        $query = $this->categoryRepository->query()
            ->select($this->selectColumns);

        if (! empty($codes)) {
            $query->whereIn('categories.code', $codes);
        }

        $categories = $query->get();

        foreach ($categories as $category) {
            $this->set($category->code, $category->id);
        }
    }

    /**
     * Get Code information
     */
    public function set(string $code, int $id): self
    {
        $this->items[$code] = $id;

        return $this;
    }

    /**
     * Check if code exists
     */
    public function has(string $code): bool
    {
        return isset($this->items[$code]);
    }

    /**
     * Get code information
     */
    public function get(string $code): ?int
    {
        if (! $this->has($code)) {
            return null;
        }

        return $this->items[$code];
    }

    /**
     * Is storage is empty
     */
    public function isEmpty(): int
    {
        return empty($this->items);
    }
}
