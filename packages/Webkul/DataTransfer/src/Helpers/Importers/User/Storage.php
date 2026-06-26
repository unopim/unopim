<?php

namespace Webkul\DataTransfer\Helpers\Importers\User;

use Webkul\Core\Repositories\LocaleRepository;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

class Storage
{
    /**
     * Items contains email as key and admin id as value
     */
    protected array $admins = [];

    /**
     * Roles contains name as key and role id as value
     */
    protected array $roles = [];

    /**
     * Locales contains code as key and locale id as value
     */
    protected array $locales = [];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->admins = [];
        $this->roles = [];
        $this->locales = [];

        $this->loadRoles();
        $this->loadLocales();
    }

    /**
     * Load the Admins by emails
     */
    public function loadAdmins(array $emails = []): void
    {
        $query = $this->adminRepository->query()
            ->select(['id', 'email']);

        if (! empty($emails)) {
            $query->whereIn('email', $emails);
        }

        $admins = $query->get();

        foreach ($admins as $admin) {
            $this->admins[$admin->email] = $admin->id;
        }
    }

    /**
     * Load the Roles
     */
    public function loadRoles(): void
    {
        $roles = $this->roleRepository->query()
            ->select(['id', 'name'])
            ->get();

        foreach ($roles as $role) {
            $this->roles[$role->name] = $role->id;
        }
    }

    /**
     * Load the Locales
     */
    public function loadLocales(): void
    {
        $locales = $this->localeRepository->query()
            ->select(['id', 'code'])
            ->get();

        foreach ($locales as $locale) {
            $this->locales[$locale->code] = $locale->id;
        }
    }

    /**
     * Get admin id by email
     */
    public function getAdminId(string $email): ?int
    {
        return $this->admins[$email] ?? null;
    }

    /**
     * Get role id by name
     */
    public function getRoleId(string $name): ?int
    {
        return $this->roles[$name] ?? null;
    }

    /**
     * Get locale id by code
     */
    public function getLocaleId(string $code): ?int
    {
        return $this->locales[$code] ?? null;
    }

    /**
     * Set admin information
     */
    public function setAdmin(string $email, int $id): self
    {
        $this->admins[$email] = $id;

        return $this;
    }
}
