<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admins')->where('type', 'api')->update(['role_id' => null]);

        $roleIds = DB::table('roles')->where('name', 'API')->pluck('id');

        foreach ($roleIds as $roleId) {
            if (DB::table('admins')->where('role_id', $roleId)->exists()) {
                continue;
            }

            DB::table('roles')->where('id', $roleId)->delete();
        }
    }

    public function down(): void {}
};
