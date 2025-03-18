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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_option_translations', function (Blueprint $table) {
            $table->dropIndex('label');

            $table->text('label')->nullable()->change();
        });
    }
};
