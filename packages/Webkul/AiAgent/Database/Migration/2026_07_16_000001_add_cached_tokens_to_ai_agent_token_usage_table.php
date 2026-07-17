<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_agent_token_usage')
            || Schema::hasColumn('ai_agent_token_usage', 'cached_tokens')) {
            return;
        }

        Schema::table('ai_agent_token_usage', function (Blueprint $table) {
            $table->unsignedBigInteger('cached_tokens')->nullable()->after('tokens_used');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_agent_token_usage')
            || ! Schema::hasColumn('ai_agent_token_usage', 'cached_tokens')) {
            return;
        }

        Schema::table('ai_agent_token_usage', function (Blueprint $table) {
            $table->dropColumn('cached_tokens');
        });
    }
};
