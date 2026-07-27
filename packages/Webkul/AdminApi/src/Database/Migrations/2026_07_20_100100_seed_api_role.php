<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\AdminApi\Support\ApiRole;
use Webkul\User\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * On a fresh install this migration runs during `migrate:fresh`, before
         * the base role seeder. The roles table is still empty, so creating the
         * API role here would claim id 1 — the id the installer hardcodes for the
         * Administrator role. The seeder's "skip when id 1 exists" guard would
         * then leave the Administrator unseeded, and the admin (role_id = 1) would
         * inherit the empty-permission API role and be locked out of the panel.
         *
         * Only ensure the API role on existing installs, where the roles table is
         * already populated so `firstOrCreate` lands on a free id. Fresh installs
         * get it on demand via {@see ApiUserProvisioner} when the first API robot
         * account is provisioned.
         */
        if (! DB::table('roles')->exists()) {
            return;
        }

        ApiRole::ensure();
    }

    public function down(): void
    {
        Role::where('name', ApiRole::NAME)->delete();
    }
};
