<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeFamily;

use Webkul\Attribute\Repositories\AttributeFamilyRepository;

class Storage
{
    /**
     * Items contains family code as key and family id as value
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
    public function __construct(protected AttributeFamilyRepository $attributeFamilyRepository) {}

    /**
     * Initialize storage — clears cache and reloads all families
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load attribute families from the database into the local cache.
     *
     * When $codes is empty every family is loaded; otherwise only the
     * families whose code is in the provided list are fetched.
     */
    public function load(array $codes = []): void
    {
        $query = $this->attributeFamilyRepository->query()
            ->select($this->selectColumns);

        if (! empty($codes)) {
            $query->whereIn('attribute_families.code', $codes);
        }

        $families = $query->get();

        foreach ($families as $family) {
            $this->set($family->code, $family->id);
        }
    }

    /**
     * Store a code → id mapping in the local cache
     */
    public function set(string $code, int $id): self
    {
        $this->items[$code] = $id;

        return $this;
    }

    /**
     * Check whether the given family code is present in the local cache
     */
    public function has(string $code): bool
    {
        return isset($this->items[$code]);
    }

    /**
     * Return the database id for the given family code, or null when not found
     */
    public function get(string $code): ?int
    {
        if (! $this->has($code)) {
            return null;
        }

        return $this->items[$code];
    }

    /**
     * Return true when the local cache is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
