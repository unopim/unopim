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
        Schema::table('magic_ai_platforms', function (Blueprint $table): void {
            $table->boolean('is_managed')->default(false)->after('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magic_ai_platforms', function (Blueprint $table): void {
            $table->dropColumn('is_managed');
        });
    }
};
