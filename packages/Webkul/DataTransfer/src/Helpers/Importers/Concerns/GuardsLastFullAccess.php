<?php

namespace Webkul\DataTransfer\Helpers\Importers\Concerns;

/**
 * Keeps at least one active admin with full access while validating a role or user
 * import, counting every demotion and removal earlier rows of the same file make.
 * The using importer provides `$adminRepository` and `skipRow()`.
 */
trait GuardsLastFullAccess
{
    /**
     * Error code for a change that leaves no active admin with full access
     */
    public const ERROR_LAST_FULL_ACCESS = 'last_full_access';

    /**
     * Active admins with full access when validation started, keyed by email
     *
     * @var array<string, array{id: int, role_id: int}>|null
     */
    protected ?array $activeFullAccessAdmins = null;

    /**
     * Full-access roles this file already demotes
     *
     * @var array<int, int>
     */
    protected array $demotedRoleIds = [];

    /**
     * Full-access admins this file already deletes, disables or moves to another role
     *
     * @var array<int, int>
     */
    protected array $removedAdminIds = [];

    /**
     * The active full-access admin with this email, loaded once per import.
     *
     * @return array{id: int, role_id: int}|null
     */
    protected function activeFullAccessAdmin(string $email): ?array
    {
        $this->activeFullAccessAdmins ??= $this->adminRepository->activeFullAccessAdmins();

        return $this->activeFullAccessAdmins[$email] ?? null;
    }

    /**
     * Reject the row when demoting the role or removing the admin leaves no active admin with full access.
     */
    protected function keepsFullAccess(int $rowNumber, string $columnName, ?int $roleId = null, ?int $adminId = null): bool
    {
        if ($roleId === null && $adminId === null) {
            return true;
        }

        $demotedRoleIds = $roleId === null ? $this->demotedRoleIds : [...$this->demotedRoleIds, $roleId];
        $removedAdminIds = $adminId === null ? $this->removedAdminIds : [...$this->removedAdminIds, $adminId];

        if (! $this->adminRepository->hasOtherActiveFullAccessAdmin($demotedRoleIds, $removedAdminIds)) {
            $this->skipRow($rowNumber, self::ERROR_LAST_FULL_ACCESS, $columnName);

            return false;
        }

        $this->demotedRoleIds = $demotedRoleIds;
        $this->removedAdminIds = $removedAdminIds;

        return true;
    }
}
