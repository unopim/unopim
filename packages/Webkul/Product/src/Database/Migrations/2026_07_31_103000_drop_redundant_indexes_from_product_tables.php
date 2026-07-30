<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Both columns already carry a unique constraint, which serves every lookup the
 * plain index would. The duplicate only costs write time — each index has to be
 * maintained on every insert and update of the row.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{index: string, column: string}>
     */
    private array $redundant = [
        'products'          => ['index' => 'products_sku_index', 'column' => 'sku'],
        'association_types' => ['index' => 'association_types_code_index', 'column' => 'code'],
    ];

    public function up(): void
    {
        foreach ($this->redundant as $table => $definition) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $definition['index'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                $blueprint->dropIndex($definition['index']);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->redundant as $table => $definition) {
            if (! Schema::hasTable($table) || Schema::hasIndex($table, $definition['index'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                $blueprint->index($definition['column']);
            });
        }
    }
};
