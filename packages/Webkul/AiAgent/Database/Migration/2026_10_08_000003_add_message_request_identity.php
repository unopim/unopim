<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_messages', fn (Blueprint $table) => $table->uuid('request_id')->nullable());
    }

    public function down(): void
    {
        Schema::table('ai_agent_messages', fn (Blueprint $table) => $table->dropColumn('request_id'));
    }
};
