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
        Schema::create('job_instances', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('entity_type');
            $table->enum('type', ['import', 'export', 'system']);

            $table->string('action');
            $table->string('validation_strategy');

            $table->integer('allowed_errors')->default(0);
            $table->char('field_separator', 1);
            $table->string('file_path');
            $table->string('images_directory_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_instances');
    }
};
