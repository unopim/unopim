<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix index names that exceed MySQL's 64-character identifier limit
     * when a DB table prefix is configured.
     *
     * On fresh installs the original migrations already use short names,
     * so this migration is a safe no-op. On upgrades with a prefix, it
     * renames the long auto-generated names to shorter ones.
     */
    public function up(): void
    {
        $prefix = DB::getTablePrefix();

        // Only needed when a table prefix is configured
        if (empty($prefix)) {
            return;
        }

        $this->safeRenameIndex(
            'attribute_option_translations',
            $prefix.'attribute_option_translations_attribute_option_id_locale_unique',
            'attr_opt_translations_opt_id_locale_unique'
        );

        $this->safeRenameIndex(
            'attribute_group_translations',
            $prefix.'attribute_group_translations_attribute_group_id_locale_unique',
            'attr_grp_translations_grp_id_locale_unique'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: reversing short names back to long names would re-introduce the problem
    }

    /**
     * Safely rename an index only if the old name exists and the new name doesn't.
     */
    private function safeRenameIndex(string $table, string $oldName, string $newName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            $prefix = DB::getTablePrefix();
            $indexes = Schema::getIndexes($prefix.$table);
            $indexNames = array_column($indexes, 'name');

            // New name already exists — nothing to do
            if (in_array($newName, $indexNames)) {
                return;
            }

            // Old long name exists — rename it
            if (in_array($oldName, $indexNames)) {
                Schema::table($table, function (Blueprint $t) use ($oldName, $newName) {
                    $t->renameIndex($oldName, $newName);
                });
            }

            // If neither exists, the original migration used a different name — leave it alone
        } catch (Throwable) {
            // Silently skip — index naming is non-critical
        }
    }
};
