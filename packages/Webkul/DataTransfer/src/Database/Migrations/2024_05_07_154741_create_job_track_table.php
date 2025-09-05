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
        Schema::create('job_track', function (Blueprint $table) {
            $table->id();
            $table->string('state')->default('pending');
            $table->string('type')->nullable();
            $table->string('action')->nullable();
            $table->string('validation_strategy')->nullable();
            $table->integer('allowed_errors')->default(0);
            $table->integer('processed_rows_count')->default(0);
            $table->integer('invalid_rows_count')->default(0);
            $table->integer('errors_count')->default(0);
            $table->json('errors')->nullable();
            $table->string('field_separator')->nullable();
            $table->string('file_path')->nullable();
            $table->string('images_directory_path')->nullable();
            $table->string('error_file_path')->nullable();
            $table->json('summary')->nullable();
            $table->json('meta')->nullable();
            $table->integer('job_instances_id')->unsigned();
            $table->foreign('job_instances_id')->references('id')->on('job_instances')->onDelete('cascade');
            $table->integer('user_id')->nullable()->unsigned();
            $table->foreign('user_id')->references('id')->on('admins')->onDelete('set null');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_track');
    }
};
