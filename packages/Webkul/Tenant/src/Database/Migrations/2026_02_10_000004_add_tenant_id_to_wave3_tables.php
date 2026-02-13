<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 3 Operational tables that receive tenant_id.
     */
    private array $tables = [
        'category_fields',
        'category_field_options',
        'attribute_options',
        'api_keys',
        'job_instances',
        'job_track',
        'notifications',
        'user_notifications',
        'magic_ai_prompts',
        'magic_ai_system_prompts',
        'webhook_logs',
        'webhook_settings',
        'oauth_clients',
    ];

    /**
     * Run the migrations.
     *
     * Add tenant_id to Wave 3 Operational Models.
     * Backfill existing rows to tenant_id = 1 (default tenant, D2).
     * Add composite index (tenant_id, id) per FR18.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('id');

                $blueprint->index(['tenant_id', 'id'], "{$table}_tenant_id_id_index");

                $blueprint->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            });

            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
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
