<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attribute_translations', function (Blueprint $table): void {
            $table->string('name', 255)->nullable()->index('attribute_translations_name')->change();

            $table->index(['name', 'attribute_id'], 'attribute_translations_attribute_id_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_translations', function (Blueprint $table): void {
            $table->dropIndex('attribute_translations_attribute_id_name');

            $table->dropIndex('attribute_translations_name');
        });

        Schema::table('attribute_translations', function (Blueprint $table): void {
            $table->text('name')->nullable()->change();
        });
    }
};
