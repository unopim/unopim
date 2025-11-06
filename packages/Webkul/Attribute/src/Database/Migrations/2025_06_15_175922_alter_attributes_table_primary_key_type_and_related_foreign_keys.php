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
                ->where('TABLE_NAME', 'attributes')
                ->where('COLUMN_NAME', 'id')
                ->value('COLUMN_TYPE');

            // Already bigint type further migration is not required
            if ($type && str_contains($type, 'bigint')) {
                return;
            }

            $foreignKeys = [
                [
                    'table' => 'attribute_translations',
                    'column' => 'attribute_id',
                ],
                [
                    'table' => 'attribute_options',
                    'column' => 'attribute_id',
                ],
                [
                    'table' => 'attribute_group_mappings',
                    'column' => 'attribute_id',
                ],
                [
                    'table' => 'product_super_attributes',
                    'column' => 'attribute_id',
                ],
            ];

            foreach ($foreignKeys as $fk) {
                Schema::table($fk['table'], function (Blueprint $table) {
                    $table->dropForeign(['attribute_id']);
                });
            }

            Schema::table('attributes', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->change();
            });

            // Attribute Translations table
            Schema::table('attribute_translations', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_id')->change();

                $table->foreign('attribute_id')
                        ->references('id')
                        ->on('attributes')
                        ->cascadeOnDelete();
            });

            // // Attribute Options
            Schema::table('attribute_options', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_id')->change();

                $table->foreign('attribute_id')
                        ->references('id')
                        ->on('attributes')
                        ->cascadeOnDelete();
            });

            // Attribute Group Mappings
            Schema::table('attribute_group_mappings', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_id')->change();

                $table->foreign('attribute_id')
                    ->references('id')
                    ->on('attributes')
                    ->cascadeOnDelete();
            });

            // Product Super Attributes
            Schema::table('product_super_attributes', function (Blueprint $table) {
                $table->unsignedBigInteger('attribute_id')->change();

                $table->foreign('attribute_id')
                    ->references('id')
                    ->on('attributes');
            });

            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        });
    }

    public function down(): void
    {
        // intentionally left empty — handled in later migration if needed
    }
};
