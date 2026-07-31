<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leading-column prefixes of wider indexes, so they answer no lookup of their
 * own and only add an entry to rewrite on every translation save.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{index: string, columns: array<int, string>}>
     */
    private array $redundant = [
        'attribute_translations' => [
            ['index' => 'attribute_translations_attribute_id_index', 'columns' => ['attribute_id']],
            ['index' => 'attribute_translations_name', 'columns' => ['name']],
        ],
        'attribute_option_translations' => [
            ['index' => 'attribute_option_translations_label', 'columns' => ['label']],
        ],
        'attribute_family_translations' => [
            ['index' => 'attribute_family_translations_name', 'columns' => ['name']],
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
