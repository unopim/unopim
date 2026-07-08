<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('measurement_family_id');
            $table->string('code');
            $table->string('symbol')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['measurement_family_id', 'code'], 'mu_family_code_unique');

            $table->foreign('measurement_family_id', 'mu_family_foreign')
                ->references('id')
                ->on('measurement_families')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_units');
    }
};
