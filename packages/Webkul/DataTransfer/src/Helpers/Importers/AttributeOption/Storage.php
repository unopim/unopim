<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeOption;

use Webkul\Attribute\Repositories\AttributeOptionRepository;

class Storage
{
    /**
     * Items contains code as key and attribute option information as value
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
    public function __construct(protected AttributeOptionRepository $attributeOptionRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the Attribute Options
     */
    public function load(array $codes = []): void
    {
        $query = $this->attributeOptionRepository->query()
            ->select($this->selectColumns);

        if ($codes !== []) {
            $query->whereIn('attribute_options.code', $codes);
        }

        $attributeOptions = $query->get();

        foreach ($attributeOptions as $attributeOption) {
            $this->set($attributeOption->code, $attributeOption->id);
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
     * Is storage empty
     */
    public function isEmpty(): int
    {
        return $this->items === [];
    }
}
