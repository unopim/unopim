<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ai_agent_memories', 'channel')) {
            return;
        }

        Schema::table('ai_agent_memories', function (Blueprint $table) {
            $table->string('channel', 64)->nullable()->after('scope');

            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ai_agent_memories', 'channel')) {
            return;
        }

        Schema::table('ai_agent_memories', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'channel']);
            $table->dropColumn('channel');
        });
    }
};
