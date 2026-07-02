<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeGroup;

use Webkul\Attribute\Repositories\AttributeGroupRepository;

class Storage
{
    /**
     * Items contains code as key and attribute group information as value
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
    public function __construct(protected AttributeGroupRepository $attributeGroupRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the Attribute Groups
     */
    public function load(array $codes = []): void
    {
        $query = $this->attributeGroupRepository->query()
            ->select($this->selectColumns);

        if (! empty($codes)) {
            $query->whereIn('attribute_groups.code', $codes);
        }

        $attributeGroups = $query->get();

        foreach ($attributeGroups as $attributeGroup) {
            $this->set($attributeGroup->code, $attributeGroup->id);
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
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
