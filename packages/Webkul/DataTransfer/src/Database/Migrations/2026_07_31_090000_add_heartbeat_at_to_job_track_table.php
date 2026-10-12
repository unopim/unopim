<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_track', function (Blueprint $table): void {
            $table->timestamp('heartbeat_at')->nullable()->after('completed_at');

            $table->index(['state', 'heartbeat_at']);
        });
    }

    public function down(): void
    {
        Schema::table('job_track', function (Blueprint $table): void {
            $table->dropIndex(['state', 'heartbeat_at']);

            $table->dropColumn('heartbeat_at');
        });
    }
};
