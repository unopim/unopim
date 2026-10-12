<?php

namespace Webkul\DataTransfer\Helpers\Importers\Role;

use Webkul\User\Repositories\RoleRepository;

class Storage
{
    /**
     * Items contains name as key and role id as value
     */
    protected array $items = [];

    /**
     * Columns which will be selected from database
     */
    protected array $selectColumns = [
        'id',
        'name',
    ];

    /**
     * Create a new helper instance.
     */
    public function __construct(protected RoleRepository $roleRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the Roles
     */
    public function load(array $names = []): void
    {
        $query = $this->roleRepository->query()
            ->select($this->selectColumns);

        if ($names !== []) {
            $query->whereIn('name', $names);
        }

        $roles = $query->get();

        foreach ($roles as $role) {
            $this->set($role->name, $role->id);
        }
    }

    /**
     * Set role information
     */
    public function set(string $name, int $id): self
    {
        $this->items[$name] = $id;

        return $this;
    }

    /**
     * Check if name exists
     */
    public function has(string $name): bool
    {
        return isset($this->items[$name]);
    }

    /**
     * Get role id by name
     */
    public function get(string $name): ?int
    {
        if (! $this->has($name)) {
            return null;
        }

        return $this->items[$name];
    }
}
