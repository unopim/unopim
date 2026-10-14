<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_messages', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->unique(['conversation_id', 'message_id'], 'ai_agent_message_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_messages', function (Blueprint $table) {
            $table->dropUnique('ai_agent_message_identity_unique');
            $table->dropColumn('deleted_at');
        });
    }
};
