<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_templates', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('passport_template_translations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('passport_template_id')->unsigned();
            $table->string('locale');
            $table->string('name');

            $table->unique(['passport_template_id', 'locale'], 'passport_template_locale_unique');

            $table->foreign('passport_template_id', 'ptt_template_foreign')
                ->references('id')
                ->on('passport_templates')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_template_translations');
        Schema::dropIfExists('passport_templates');
    }
};
