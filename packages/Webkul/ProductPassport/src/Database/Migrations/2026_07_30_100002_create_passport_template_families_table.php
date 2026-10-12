<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A family resolves to exactly one template, so "which passport does this
     * product get" never needs precedence rules — hence the unique on
     * `attribute_family_id` rather than on the pair.
     */
    public function up(): void
    {
        Schema::create('passport_template_families', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('passport_template_id')->unsigned();
            $table->integer('attribute_family_id')->unsigned();

            $table->unique('attribute_family_id', 'passport_template_family_unique');

            $table->foreign('passport_template_id', 'ptf_template_foreign')
                ->references('id')
                ->on('passport_templates')
                ->onDelete('cascade');

            $table->foreign('attribute_family_id', 'ptf_family_foreign')
                ->references('id')
                ->on('attribute_families')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_template_families');
    }
};
