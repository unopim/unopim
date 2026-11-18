<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_instances', function (Blueprint $table): void {
            $table->integer('updated_by')->nullable()->unsigned()->index();

            $table->integer('file_uploaded_by')->nullable()->unsigned()->index();
        });
    }

    public function down(): void
    {
        Schema::table('job_instances', function (Blueprint $table): void {
            $table->dropIndex(['updated_by']);

            $table->dropIndex(['file_uploaded_by']);

            $table->dropColumn(['updated_by', 'file_uploaded_by']);
        });
    }
};
