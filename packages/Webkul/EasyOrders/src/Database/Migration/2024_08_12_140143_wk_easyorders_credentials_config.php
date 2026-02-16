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
        Schema::create('wk_easyorders_credentials_config', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('merchant_id')->nullable();
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(false);
            $table->string('store_name')->nullable();
            $table->json('store_locale_mapping')->nullable();
            $table->json('store_locales')->nullable();
            $table->boolean('default_set')->default(false);
            $table->json('extras')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_easyorders_credentials_config');
    }
};
