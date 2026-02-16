<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 7 Shopify connector tables that receive tenant_id.
     *
     * - wk_shopify_credentials_config      – Shopify store credentials
     * - shopify_setting_configuration_values – Export mapping/settings
     * - wk_shopify_data_mapping             – Entity mapping (UnoPim ↔ Shopify)
     * - wk_shopify_metafield_defination     – Metafield definitions
     */
    private array $tables = [
        'wk_shopify_credentials_config',
        'shopify_setting_configuration_values',
        'wk_shopify_data_mapping',
        'wk_shopify_metafield_defination',
    ];

    /**
     * Run the migrations.
     *
     * Add tenant_id to Wave 7 Shopify Tables.
     * Add composite index (tenant_id, id).
     * Add FK → tenants.id with nullOnDelete.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');

                $blueprint->index(['tenant_id', 'id'], "{$table}_tenant_id_id_index");

                $blueprint->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(["{$table}_tenant_id_foreign"]);
                $blueprint->dropIndex("{$table}_tenant_id_id_index");
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
