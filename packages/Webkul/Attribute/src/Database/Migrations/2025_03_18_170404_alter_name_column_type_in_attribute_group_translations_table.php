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
        Schema::table('attribute_group_translations', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->index('attribute_group_translations_name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_group_translations', function (Blueprint $table) {
            $table->dropIndex('name');

            $table->text('name')->nullable()->change();
        });
    }
};
