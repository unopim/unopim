<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seal the installer on instances that were already installed before the
     * persistent "installer.installed" flag existed, so losing the ephemeral
     * storage marker cannot reopen the installer for takeover.
     */
    public function up(): void
    {
        if (! Schema::hasTable('core_config') || ! Schema::hasTable('admins')) {
            return;
        }

        if (! DB::table('admins')->exists()) {
            return;
        }

        DB::table('core_config')->updateOrInsert(
            ['code' => 'installer.installed'],
            ['value' => '1']
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('core_config')) {
            DB::table('core_config')->where('code', 'installer.installed')->delete();
        }
    }
};
