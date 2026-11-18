<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

return new class extends Migration
{
    /**
     * Store every role whose permission type is not exactly "all" or "custom" as "custom", matching how
     * authorization already treats it, so a collation-equal value such as "àll" can no longer pass SQL checks.
     */
    public function up(): void
    {
        DB::table('roles')
            ->select(['id', 'permission_type'])
            ->lazyById()
            ->reject(fn (object $role): bool => in_array($role->permission_type, ['all', 'custom'], true))
            ->pluck('id')
            ->chunk(500)
            ->each(fn (LazyCollection $ids): int => DB::table('roles')->whereIn('id', $ids->all())->update(['permission_type' => 'custom']));
    }

    public function down(): void {}
};
