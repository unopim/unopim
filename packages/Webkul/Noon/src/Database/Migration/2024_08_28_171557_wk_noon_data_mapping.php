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
        Schema::create('wk_noon_data_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->string('code');
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('job_instance_id')->nullable();
            $table->string('related_id')->nullable();
            $table->string('related_source')->nullable();
            $table->string('api_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_noon_data_mapping');
    }
};
