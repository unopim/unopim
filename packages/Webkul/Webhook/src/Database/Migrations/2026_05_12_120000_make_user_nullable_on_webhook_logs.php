<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table): void {
            $table->string('user')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table): void {
            $table->string('user')->nullable(false)->change();
        });
    }
};
