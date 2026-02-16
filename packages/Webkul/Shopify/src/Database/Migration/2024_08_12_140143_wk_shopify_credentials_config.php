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
        Schema::create('wk_shopify_credentials_config', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('shopUrl')->unique();
            $table->string('accessToken');
            $table->boolean('active')->default(false);
            $table->string('apiVersion')->nullable();
            $table->json('storelocaleMapping')->nullable();
            $table->json('storeLocales')->nullable();
            $table->boolean('defaultSet')->default(false);
            $table->string('resources')->nullable();
            $table->json('extras')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_shopify_credentials_config');
    }
};
