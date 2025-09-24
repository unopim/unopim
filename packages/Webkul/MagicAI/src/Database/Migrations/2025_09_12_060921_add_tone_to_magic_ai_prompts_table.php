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
        Schema::table('magic_ai_prompts', function (Blueprint $table) {
            $table->unsignedBigInteger('tone')->nullable()->after('type');
            $table->foreign('tone')
                ->references('id')
                ->on('magic_ai_system_prompts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magic_ai_prompts', function (Blueprint $table) {
            $table->dropColumn('tone');
        });
    }
};
