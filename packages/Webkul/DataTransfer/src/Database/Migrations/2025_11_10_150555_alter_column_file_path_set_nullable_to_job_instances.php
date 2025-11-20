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
        Schema::table('job_instances', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->char('field_separator', 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_instances', function (Blueprint $table) {
            $table->string('file_path')->change();
            $table->char('field_separator', 1)->change();
        });
    }
};
