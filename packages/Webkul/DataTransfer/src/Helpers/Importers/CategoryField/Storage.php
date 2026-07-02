<?php

namespace Webkul\DataTransfer\Helpers\Importers\CategoryField;

use Webkul\Category\Repositories\CategoryFieldRepository;

class Storage
{
    /**
     * Items contains code as key and category field id as value
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
    public function __construct(protected CategoryFieldRepository $categoryFieldRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the category fields
     */
    public function load(array $codes = []): void
    {
        $query = $this->categoryFieldRepository->query()
            ->select($this->selectColumns);

        if (! empty($codes)) {
            $query->whereIn('category_fields.code', $codes);
        }

        $categoryFields = $query->get();

        foreach ($categoryFields as $categoryField) {
            $this->set($categoryField->code, $categoryField->id);
        }
    }

    /**
     * Set code => id mapping
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
     * Get id for a given code
     */
    public function get(string $code): ?int
    {
        if (! $this->has($code)) {
            return null;
        }

        return $this->items[$code];
    }

    /**
     * Is storage empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
