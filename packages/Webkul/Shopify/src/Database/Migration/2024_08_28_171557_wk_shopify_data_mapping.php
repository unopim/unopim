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
        Schema::create('wk_shopify_data_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('entityType', 255)->nullable();
            $table->string('code', 255)->nullable();
            $table->string('externalId', 255)->nullable();
            $table->string('relatedId', 255)->nullable();
            $table->integer('jobInstanceId');
            $table->string('relatedSource', 255)->nullable();
            $table->string('apiUrl', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_shopify_data_mapping');
    }
};
