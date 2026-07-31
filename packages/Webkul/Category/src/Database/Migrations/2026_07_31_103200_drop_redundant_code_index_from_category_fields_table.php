<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $index = $this->plainIndex('category_fields', ['code']);

        if ($index === null) {
            return;
        }

        Schema::table('category_fields', function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    public function down(): void
    {
        if ($this->plainIndex('category_fields', ['code']) !== null) {
            return;
        }

        Schema::table('category_fields', function (Blueprint $table): void {
            $table->index('code');
        });
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
