<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_template_fields', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('passport_template_id')->unsigned();
            $table->integer('passport_template_section_id')->unsigned()->nullable();
            $table->string('code');
            $table->string('source_type')->default('attribute');
            $table->integer('attribute_id')->unsigned()->nullable();
            $table->string('tier')->default('consumer');
            $table->boolean('is_required')->default(false);

            /**
             * Reserved payload slots (gtin/model/batch) feed the identifier block
             * and the data carrier instead of rendering as a row. NULL means an
             * ordinary field; both MySQL and PostgreSQL allow repeated NULLs
             * under the unique index, so only real roles are constrained.
             */
            $table->string('role')->nullable();
            $table->integer('position')->default(0);

            $table->unique(['passport_template_id', 'code'], 'passport_template_field_code_unique');
            $table->unique(['passport_template_id', 'role'], 'passport_template_field_role_unique');

            $table->foreign('passport_template_id', 'ptfi_template_foreign')
                ->references('id')
                ->on('passport_templates')
                ->onDelete('cascade');

            $table->foreign('passport_template_section_id', 'ptfi_section_foreign')
                ->references('id')
                ->on('passport_template_sections')
                ->onDelete('set null');

            $table->foreign('attribute_id', 'ptfi_attribute_foreign')
                ->references('id')
                ->on('attributes')
                ->onDelete('set null');
        });

        Schema::create('passport_template_field_translations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('passport_template_field_id')->unsigned();
            $table->string('locale');
            $table->string('label');
            $table->text('fixed_value')->nullable();

            $table->unique(['passport_template_field_id', 'locale'], 'passport_template_field_locale_unique');

            $table->foreign('passport_template_field_id', 'ptfit_field_foreign')
                ->references('id')
                ->on('passport_template_fields')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_template_field_translations');
        Schema::dropIfExists('passport_template_fields');
    }
};
