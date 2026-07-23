<?php

namespace Webkul\DataTransfer\Helpers\Importers\Attribute;

use Webkul\Attribute\Repositories\AttributeRepository;

class Storage
{
    /**
     * Items contains name as key and attribute information as value
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
     */
    public function __construct(protected AttributeRepository $attributeRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the Attributes
     */
    public function load(array $codes = []): void
    {
        $query = $this->attributeRepository->query()
            ->select($this->selectColumns);

        if ($codes !== []) {
            $query->whereIn('attributes.code', $codes);
        }

        $attributes = $query->get();

        foreach ($attributes as $attribute) {
            $this->set($attribute->code, $attribute->id);
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
        return $this->items === [];
    }
}
