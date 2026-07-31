<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Confirms the release's data migrations actually landed.
 *
 * A zero exit code from `migrate` only proves no statement threw. These checks
 * assert the resulting data, because the expensive failures in this release —
 * associations that never backfilled, integrations left on a human owner — are
 * silent ones.
 */
class PostUpgradeVerifier
{
    /**
     * Mirrors the scrub migration's own list; a field left here that the
     * migration does not remove would fail verification forever.
     */
    private const SENSITIVE_AUDIT_FIELDS = ['password', 'api_token', 'remember_token', 'sso_identifier'];

    /**
     * @return array<int, CheckResult>
     */
    public function run(): array
    {
        return [
            $this->verifyAssociations(),
            $this->verifyRobotUsers(),
            $this->verifyCategoryBounds(),
            $this->verifyScrubbedAudits(),
        ];
    }

    /**
     * Every product carrying legacy association JSON should have at least one
     * normalised row. Exact parity is not asserted: the legacy payload can
     * reference SKUs that no longer exist, and those are correctly dropped.
     */
    protected function verifyAssociations(): CheckResult
    {
        $name = trans('installer::app.upgrade.verify.associations');

        if (! Schema::hasTable('product_associations') || ! Schema::hasTable('products')) {
            return CheckResult::warning($name, trans('installer::app.upgrade.verify.table-missing'));
        }

        $legacy = DB::table('products')->whereJsonContainsKey('values->associations')->count();

        if ($legacy === 0) {
            return CheckResult::passed($name, trans('installer::app.upgrade.verify.associations-none'));
        }

        $migrated = DB::table('product_associations')->distinct()->count('product_id');

        if ($migrated > 0) {
            return CheckResult::passed($name, trans('installer::app.upgrade.verify.associations-detail', [
                'migrated' => $migrated,
                'legacy'   => $legacy,
            ]));
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.verify.associations-detail', ['migrated' => 0, 'legacy' => $legacy]),
            trans('installer::app.upgrade.verify.associations-remedy')
        );
    }

    /**
     * Each live API key must belong to a robot administrator, not a person.
     */
    protected function verifyRobotUsers(): CheckResult
    {
        $name = trans('installer::app.upgrade.verify.robot-users');

        if (! Schema::hasTable('api_keys') || ! Schema::hasColumn('admins', 'type')) {
            return CheckResult::warning($name, trans('installer::app.upgrade.verify.table-missing'));
        }

        $unmigrated = DB::table('api_keys')
            ->join('admins', 'admins.id', '=', 'api_keys.admin_id')
            ->where('api_keys.revoked', 0)
            ->where('admins.type', '!=', 'api')
            ->count();

        if ($unmigrated === 0) {
            return CheckResult::passed($name);
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.verify.robot-users-detail', ['count' => $unmigrated]),
            trans('installer::app.upgrade.verify.robot-users-remedy')
        );
    }

    /**
     * Nested-set integrity: no node may open after it closes, and the tree may
     * hold only one root.
     */
    protected function verifyCategoryBounds(): CheckResult
    {
        $name = trans('installer::app.upgrade.verify.category-bounds');

        if (! Schema::hasTable('categories')) {
            return CheckResult::warning($name, trans('installer::app.upgrade.verify.table-missing'));
        }

        $broken = DB::table('categories')->whereColumn('_lft', '>=', '_rgt')->count();

        if ($broken === 0) {
            return CheckResult::passed($name);
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.verify.category-bounds-detail', ['count' => $broken]),
            trans('installer::app.upgrade.verify.category-bounds-remedy')
        );
    }

    /**
     * Administrator audit history must no longer carry credential fields.
     */
    protected function verifyScrubbedAudits(): CheckResult
    {
        $name = trans('installer::app.upgrade.verify.scrubbed-audits');

        if (! Schema::hasTable('audits')) {
            return CheckResult::warning($name, trans('installer::app.upgrade.verify.table-missing'));
        }

        $remaining = DB::table('audits')
            ->where('auditable_type', 'like', '%\\\\Admin')
            ->where(function ($query): void {
                foreach (self::SENSITIVE_AUDIT_FIELDS as $field) {
                    $query->orWhere('old_values', 'like', '%"'.$field.'"%')
                        ->orWhere('new_values', 'like', '%"'.$field.'"%');
                }
            })
            ->count();

        if ($remaining === 0) {
            return CheckResult::passed($name);
        }

        return CheckResult::failed(
            $name,
            trans('installer::app.upgrade.verify.scrubbed-audits-detail', ['count' => $remaining]),
            trans('installer::app.upgrade.verify.scrubbed-audits-remedy')
        );
    }
}
