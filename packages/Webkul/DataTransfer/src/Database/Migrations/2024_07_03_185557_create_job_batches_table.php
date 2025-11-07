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
        Schema::create('job_track_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('state')->default('pending');
            $table->json('data');
            $table->json('summary')->nullable();

            $table->integer('job_track_id')->unsigned();

            $table->foreign('job_track_id')->references('id')->on('job_track')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_track_batches');
    }
};
