<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_template_sections', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('passport_template_id')->unsigned();
            $table->string('code');
            $table->integer('position')->default(0);

            $table->unique(['passport_template_id', 'code'], 'passport_template_section_code_unique');

            $table->foreign('passport_template_id', 'pts_template_foreign')
                ->references('id')
                ->on('passport_templates')
                ->onDelete('cascade');
        });

        Schema::create('passport_template_section_translations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('passport_template_section_id')->unsigned();
            $table->string('locale');
            $table->string('name');

            $table->unique(['passport_template_section_id', 'locale'], 'passport_template_section_locale_unique');

            $table->foreign('passport_template_section_id', 'ptst_section_foreign')
                ->references('id')
                ->on('passport_template_sections')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_template_section_translations');
        Schema::dropIfExists('passport_template_sections');
    }
};
