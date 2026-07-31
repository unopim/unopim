<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<int, array{columns: array<int, string>, name: string|null}>>
     */
    private array $redundant = [
        'products' => [
            ['columns' => ['attribute_family_id'], 'name' => null],
            ['columns' => ['status'], 'name' => null],
        ],
        'product_associations' => [
            ['columns' => ['product_id', 'association_type_id'], 'name' => 'product_assoc_product_type_index'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->redundant as $table => $definitions) {
            foreach ($definitions as $definition) {
                $index = $this->plainIndex($table, $definition['columns']);

                if ($index === null) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                    $blueprint->dropIndex($index);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->redundant as $table => $definitions) {
            foreach ($definitions as $definition) {
                if (! Schema::hasTable($table) || $this->plainIndex($table, $definition['columns']) !== null) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                    $blueprint->index($definition['columns'], $definition['name']);
                });
            }
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function plainIndex(string $table, array $columns): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === $columns && ! $index['unique'] && ! $index['primary']) {
                return $index['name'];
            }
        }

        return null;
    }
};
