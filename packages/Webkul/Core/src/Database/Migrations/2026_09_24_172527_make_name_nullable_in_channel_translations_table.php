<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('channel_translations', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('channel_translations')->whereNull('name')->update(['name' => '']);

        Schema::table('channel_translations', function (Blueprint $table): void {
            $table->string('name')->nullable(false)->change();
        });
    }
};
