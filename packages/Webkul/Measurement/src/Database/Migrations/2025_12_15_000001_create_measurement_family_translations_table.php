<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_family_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('measurement_family_id');
            $table->string('locale');
            $table->text('label')->nullable();

            $table->unique(['measurement_family_id', 'locale'], 'mft_family_locale_unique');

            $table->foreign('measurement_family_id', 'mft_family_foreign')
                ->references('id')
                ->on('measurement_families')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_family_translations');
    }
};
