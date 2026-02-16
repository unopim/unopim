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
        Schema::create('wk_magento2_export_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('external_field');
            $table->unsignedBigInteger('job_instance_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_magento2_export_mapping');
    }
};
