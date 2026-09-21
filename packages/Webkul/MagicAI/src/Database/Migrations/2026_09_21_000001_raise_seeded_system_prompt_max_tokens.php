<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\MagicAI;

return new class extends Migration
{
    /**
     * The original seeder wrote a 1024 token ceiling, which truncates HTML
     * output mid-tag. Only rows still holding that exact value are raised, so
     * a ceiling an admin tuned themselves is left alone.
     */
    private const SEEDED_MAX_TOKENS = 1024;

    public function up(): void
    {
        DB::table('magic_ai_system_prompts')
            ->where('max_tokens', self::SEEDED_MAX_TOKENS)
            ->update(['max_tokens' => MagicAI::DEFAULT_MAX_TOKENS]);
    }

    public function down(): void
    {
        DB::table('magic_ai_system_prompts')
            ->where('max_tokens', MagicAI::DEFAULT_MAX_TOKENS)
            ->update(['max_tokens' => self::SEEDED_MAX_TOKENS]);
    }
};
