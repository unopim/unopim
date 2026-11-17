<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_instances', function (Blueprint $table): void {
            $table->integer('updated_by')->nullable()->unsigned();

            $table->foreign('updated_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('job_instances', function (Blueprint $table): void {
            $table->dropForeign(['updated_by']);

            $table->dropColumn('updated_by');
        });
    }
};
