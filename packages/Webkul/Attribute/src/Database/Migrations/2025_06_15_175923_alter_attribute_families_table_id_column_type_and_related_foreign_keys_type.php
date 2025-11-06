<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ('mysql' !== DB::getDriverName()) {
            return;
        }

        DB::transaction(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

            $type = DB::table('information_schema.columns')
                ->select('COLUMN_TYPE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'attribute_families')
                ->where('COLUMN_NAME', 'id')
                ->value('COLUMN_TYPE');

            if ($type && str_contains($type, 'bigint')) {
                return;
            }

            $foreignKeys = [
                [
                    'table'  => 'attribute_family_translations',
                    'column' => 'attribute_family_id',
                ],
                [
                    'table'  => 'products',
                    'column' => 'attribute_family_id',
                ],
                [
                    'table' => 'attribute_family_group_mappings',
                    'column' => 'attribute_family_id',
                ],
            ];

            // Drop foreign keys referencing attribute_families.id
            foreach ($foreignKeys as $fk) {
                Schema::table($fk['table'], function (Blueprint $table) use ($fk) {
                    $table->dropForeign([$fk['column']]);
                });
            }

            Schema::table('attribute_families', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->change();
            });

            // attribute_family_translations
            Schema::table('attribute_family_translations', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_family_id')->change();

                $table->foreign('attribute_family_id')
                    ->references('id')
                    ->on('attribute_families')
                    ->cascadeOnDelete();
            });

            // Products
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_family_id')->change();

                $table->foreign('attribute_family_id')
                    ->references('id')
                    ->nullable()
                    ->on('attribute_families');
            });

            // attribute family group mappings
            Schema::table('attribute_family_group_mappings', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_family_id')->change();

                $table->foreign('attribute_family_id')
                    ->references('id')
                    ->on('attribute_families')
                    ->cascadeOnDelete();
            });

            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        });
    }

    public function down(): void
    {
    }
};
