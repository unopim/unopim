<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_unit_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('measurement_unit_id');
            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['measurement_unit_id', 'locale'], 'mut_unit_locale_unique');

            $table->foreign('measurement_unit_id', 'mut_unit_foreign')
                ->references('id')
                ->on('measurement_units')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_unit_translations');
    }
};
