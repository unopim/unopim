<?php

namespace Webkul\DataTransfer\Helpers\Importers\Concerns;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Keeps at least one active admin with full access through a role or user import,
 * counting every demotion and removal earlier rows of the same file make: once while
 * validating, and again under a lock while each batch is written.
 * The using importer provides `$adminRepository`, `$roleRepository` and `skipRow()`.
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
     * Roles this file already takes full access away from
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
     * Lock full access for the current transaction and restart the bookkeeping from the locked state,
     * so a batch being written re-checks every row against what is true now, not at validation.
     */
    protected function lockFullAccess(): void
    {
        $this->roleRepository->lockFullAccessRoles();

        $this->activeFullAccessAdmins = $this->adminRepository->activeFullAccessAdmins(lock: true);
        $this->demotedRoleIds = [];
        $this->removedAdminIds = [];
    }

    /**
     * Write a batch in one transaction that first locks full access, so every row is re-checked as it is written.
     */
    /**
     * Record in the job log a validated row the write had to skip because access changed since validation.
     */
    protected function logSkippedRow(string $row, string $reason): void
    {
        $this->jobLogger?->warning("Row '{$row}' passed validation but was not imported: {$reason}");
    }

    protected function writeWithFullAccessLock(Closure $write): void
    {
        DB::transaction(function () use ($write): void {
            $this->lockFullAccess();

            $this->forgetImportActors();

            $write();
        });
    }

    /**
     * Reject the row when demoting the role or removing the admin leaves no active admin with full access.
     */
    protected function keepsFullAccess(int $rowNumber, string $columnName, ?int $roleId = null, ?int $adminId = null): bool
    {
        if ($this->allowsFullAccessChange($roleId, $adminId)) {
            return true;
        }

        $this->skipRow($rowNumber, self::ERROR_LAST_FULL_ACCESS, $columnName);

        return false;
    }

    /**
     * Record the demotion or removal and allow it, unless it leaves no active admin with full access.
     */
    protected function allowsFullAccessChange(?int $roleId = null, ?int $adminId = null): bool
    {
        if ($roleId === null && $adminId === null) {
            return true;
        }

        $demotedRoleIds = $roleId === null ? $this->demotedRoleIds : [...$this->demotedRoleIds, $roleId];
        $removedAdminIds = $adminId === null ? $this->removedAdminIds : [...$this->removedAdminIds, $adminId];

        $remainingCount = $this->remainingFullAccessCount($demotedRoleIds, $removedAdminIds);

        if ($remainingCount === 0 && $remainingCount < $this->remainingFullAccessCount($this->demotedRoleIds, $this->removedAdminIds)) {
            return false;
        }

        $this->demotedRoleIds = $demotedRoleIds;
        $this->removedAdminIds = $removedAdminIds;

        return true;
    }

    /**
     * Number of active full-access admins left after demoting the roles and removing the admins.
     *
     * @param  array<int, int>  $demotedRoleIds
     * @param  array<int, int>  $removedAdminIds
     */
    protected function remainingFullAccessCount(array $demotedRoleIds, array $removedAdminIds): int
    {
        $this->activeFullAccessAdmins ??= $this->adminRepository->activeFullAccessAdmins();

        return count(array_filter(
            $this->activeFullAccessAdmins,
            fn (array $admin): bool => ! in_array($admin['role_id'], $demotedRoleIds, true)
                && ! in_array($admin['id'], $removedAdminIds, true)
        ));
    }
}
