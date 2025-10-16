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
        Schema::create('attribute_family_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_family_id')->constrained('attribute_families')->cascadeOnDelete();

            $table->string('locale');
            $table->text('name')->nullable();

            $table->unique(['attribute_family_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_family_translations');
    }
};
