<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        $fallbackRoleId = DB::table('roles')->min('id');

        if (! $fallbackRoleId) {
            return;
        }

        DB::table('admins')->whereNull('role_id')->update(['role_id' => $fallbackRoleId]);

        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->nullable(false)->change();
        });
    }
};
