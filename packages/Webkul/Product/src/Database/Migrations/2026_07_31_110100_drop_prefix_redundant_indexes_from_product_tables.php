<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leading-column prefixes of wider indexes, so they answer no lookup of their
 * own and only add an entry to rewrite on every product write.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, array{index: string, columns: array<int, string>}>>
     */
    private array $redundant = [
        'products' => [
            ['index' => 'products_attribute_family_id_index', 'columns' => ['attribute_family_id']],
            ['index' => 'products_status_index', 'columns' => ['status']],
        ],
        'product_associations' => [
            ['index' => 'product_assoc_product_type_index', 'columns' => ['product_id', 'association_type_id']],
        ],
    ];

    public function up(): void
    {
        foreach ($this->redundant as $table => $definitions) {
            foreach ($definitions as $definition) {
                if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $definition['index'])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                    $blueprint->dropIndex($definition['index']);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->redundant as $table => $definitions) {
            foreach ($definitions as $definition) {
                if (! Schema::hasTable($table) || Schema::hasIndex($table, $definition['index'])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                    $blueprint->index($definition['columns'], $definition['index']);
                });
            }
        }
    }
};
