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
        Schema::table('attribute_option_translations', function (Blueprint $table) {
            $table->string('label', 255)->nullable()->index('attribute_option_translations_label')->change();

            $table->index(['label', 'attribute_option_id'], 'attribute_option_translations_option_id_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_option_translations', function (Blueprint $table) {
            $table->dropIndex('attribute_option_translations_option_id_label');

            $table->dropIndex('attribute_option_translations_label');
        });

        Schema::table('attribute_option_translations', function (Blueprint $table) {
            $table->text('label')->nullable()->change();
        });
    }
};
