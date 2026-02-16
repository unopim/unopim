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
        // Note: Table name contains a typo ("defination" instead of "definition").
        // Retained for backwards compatibility with existing deployments.
        Schema::create('wk_shopify_metafield_defination', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('code', 255);
            $table->string('attribute', 255);
            $table->string('name_space', 255);
            $table->string('name_space_key', 255);
            $table->string('description', 255);
            $table->string('type', 255);
            $table->string('ownerType', 255);
            $table->json('validations')->nullable();
            $table->boolean('pin')->default(false);
            $table->boolean('listvalue')->default(false);
            $table->string('ContentTypeName', 255);
            $table->string('ownerTypeName', 255);
            $table->string('attributeLabel', 255);
            $table->json('options')->nullable();
            $table->boolean('storefronts')->default(true);
            $table->json('apiUrl')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_shopify_metafield_defination');
    }
};
