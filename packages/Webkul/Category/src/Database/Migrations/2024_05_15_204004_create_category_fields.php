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
        Schema::create('category_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('type');
            $table->string('validation')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('is_required')->default(0);
            $table->boolean('is_unique')->default(0);
            $table->boolean('status')->default(0);
            $table->string('section', 10);
            $table->boolean('value_per_locale')->default(0);
            $table->boolean('enable_wysiwyg')->default(0);
            $table->string('regex_pattern')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('type');
            $table->index('status');
            $table->index('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};
