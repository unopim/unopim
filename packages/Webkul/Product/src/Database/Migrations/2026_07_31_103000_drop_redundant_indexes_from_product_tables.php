<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $redundant = [
        'products'          => ['sku'],
        'association_types' => ['code'],
    ];

    public function up(): void
    {
        foreach ($this->redundant as $table => $columns) {
            $index = $this->plainIndex($table, $columns);

            if ($index === null) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->redundant as $table => $columns) {
            if (! Schema::hasTable($table) || $this->plainIndex($table, $columns) !== null) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->index($columns);
            });
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
