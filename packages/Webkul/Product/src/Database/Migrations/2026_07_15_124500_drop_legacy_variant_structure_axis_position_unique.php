<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The pre-level-column unique key that must not survive alongside the
     * (variant_structure_id, level, position) key added by the previous
     * migration. Note: that composite key is left alone here — it also
     * backs the `variant_structure_id` foreign key, so dropping it would
     * fail with "Cannot drop index ...: needed in a foreign key constraint".
     */
    private const string LEGACY_INDEX = 'vsax_structure_position_unique';

    public function up(): void
    {
        if (! Schema::hasTable('variant_structure_axes')) {
            return;
        }

        if ($this->indexExists('variant_structure_axes', self::LEGACY_INDEX)) {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->dropUnique(self::LEGACY_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('variant_structure_axes')) {
            return;
        }

        if (! $this->indexExists('variant_structure_axes', self::LEGACY_INDEX)) {
            Schema::table('variant_structure_axes', function (Blueprint $table): void {
                $table->unique(['variant_structure_id', 'position'], self::LEGACY_INDEX);
            });
        }
    }

    /**
     * Check whether an index exists (prefix-aware, mysql + pgsql).
     */
    private function indexExists(string $table, string $index): bool
    {
        $prefix = DB::getTablePrefix();

        if (DB::getDriverName() === 'pgsql') {
            return (bool) DB::select('SELECT 1 FROM pg_indexes WHERE indexname = ?', [$index]);
        }

        foreach (DB::select("SHOW INDEX FROM `{$prefix}{$table}`") as $row) {
            if (($row->Key_name ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
