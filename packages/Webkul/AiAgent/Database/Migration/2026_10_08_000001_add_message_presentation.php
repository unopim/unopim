<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_messages', function (Blueprint $table) {
            $table->string('message_id', 64)->nullable();
            $table->json('presentation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_messages', fn (Blueprint $table) => $table->dropColumn(['message_id', 'presentation']));
    }
};
