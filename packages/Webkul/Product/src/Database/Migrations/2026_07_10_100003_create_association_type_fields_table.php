<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_type_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('association_type_id')->unsigned();
            $table->string('code');
            $table->string('type');
            $table->string('validation')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('is_required')->default(0);
            $table->boolean('is_unique')->default(0);
            $table->boolean('status')->default(1);
            $table->string('section', 10)->default('left');
            $table->boolean('value_per_locale')->default(0);
            $table->boolean('enable_wysiwyg')->default(0);
            $table->string('regex_pattern')->nullable();
            $table->timestamps();

            $table->unique(['code', 'association_type_id'], 'unique_code_association_type_id');
            $table->foreign('association_type_id')->references('id')->on('association_types')->onDelete('cascade');
            $table->index('code');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_type_fields');
    }
};
