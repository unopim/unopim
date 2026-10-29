<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeOption;

use Webkul\Attribute\Repositories\AttributeOptionRepository;

class Storage
{
    /**
     * Attribute ID => option code => option ID.
     */
    protected array $items = [];

    /**
     * Columns which will be selected from database
     */
    protected array $selectColumns = [
        'id',
        'code',
        'attribute_id',
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
        $this->load();
    }

    /**
     * Load all identities for the requested codes, including ambiguous codes.
     */
    public function load(array $codes = []): void
    {
        $this->items = [];

        $query = $this->attributeOptionRepository->query()
            ->select($this->selectColumns);

        if ($codes !== []) {
            $query->whereIn('attribute_options.code', $codes);
        }

        foreach ($query->get() as $attributeOption) {
            $this->set($attributeOption->code, $attributeOption->id, $attributeOption->attribute_id);
        }
    }

    /**
     * Legacy writes resolve the persisted ID and require its code to match.
     * Unknown IDs or mismatched codes leave storage unchanged. Internal callers
     * may supply the known attribute ID to avoid another database lookup.
     */
    public function set(string $code, int $id, ?int $attributeId = null): self
    {
        if ($attributeId === null) {
            $option = $this->attributeOptionRepository->find($id, $this->selectColumns);

            if ($option === null || $option->code !== $code) {
                return $this;
            }

            $attributeId = $option->attribute_id;
        }

        $this->items[$attributeId][$code] = $id;

        return $this;
    }

    public function has(string $code, ?int $attributeId = null): bool
    {
        return $this->get($code, $attributeId) !== null;
    }

    /**
     * Legacy code-only lookups are safe only when exactly one attribute owns it.
     */
    public function get(string $code, ?int $attributeId = null): ?int
    {
        if ($attributeId !== null) {
            return $this->items[$attributeId][$code] ?? null;
        }

        $id = null;

        foreach ($this->items as $options) {
            if (isset($options[$code])) {
                if ($id !== null) {
                    return null;
                }

                $id = $options[$code];
            }
        }

        return $id;
    }

    /**
     * Is storage empty
     */
    public function isEmpty(): int
    {
        return $this->items === [];
    }
}
