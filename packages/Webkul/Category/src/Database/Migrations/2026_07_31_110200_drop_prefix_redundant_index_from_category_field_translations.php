<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $index = $this->plainIndex('category_field_translations', ['category_field_id']);

        if ($index === null) {
            return;
        }

        Schema::table('category_field_translations', function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    public function down(): void
    {
        if ($this->plainIndex('category_field_translations', ['category_field_id']) !== null) {
            return;
        }

        Schema::table('category_field_translations', function (Blueprint $table): void {
            $table->index('category_field_id');
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
