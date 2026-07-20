<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persists per-user daily token usage (total + cached) into the
 * ai_agent_token_usage table with a lock-guarded upsert, so concurrent
 * requests never lose increments.
 */
class TokenUsageRecorder
{
    /**
     * Whether the cached_tokens column exists (memoized per process —
     * the schema does not change at runtime).
     */
    protected static ?bool $hasCachedTokensColumn = null;

    /**
     * Record token usage for the given admin user (null = system/queue).
     */
    public function record(?int $userId, int $tokensUsed, int $cachedTokens = 0): void
    {
        if ($tokensUsed <= 0) {
            return;
        }

        try {
            $this->persist($userId, $tokensUsed, $cachedTokens);
        } catch (UniqueConstraintViolationException) {
            // Two first-of-day requests raced past the row check (possible
            // under READ COMMITTED, where the lock cannot gap-lock a missing
            // row); the row now exists, so a single retry takes the update path.
            $this->persist($userId, $tokensUsed, $cachedTokens);
        }
    }

    /**
     * Run the lock-guarded daily upsert once.
     */
    protected function persist(?int $userId, int $tokensUsed, int $cachedTokens): void
    {
        $cachedTokens = max(0, $cachedTokens);

        DB::transaction(function () use ($userId, $tokensUsed, $cachedTokens): void {
            $today = now()->toDateString();

            $row = DB::table('ai_agent_token_usage')
                ->where('user_id', $userId)
                ->where('usage_date', $today)
                ->lockForUpdate()
                ->first();

            $withCache = $this->hasCachedTokensColumn();

            if ($row) {
                $values = [
                    'tokens_used'   => $row->tokens_used + $tokensUsed,
                    'request_count' => $row->request_count + 1,
                    'updated_at'    => now(),
                ];

                if ($withCache) {
                    $values['cached_tokens'] = ($row->cached_tokens ?? 0) + $cachedTokens;
                }

                DB::table('ai_agent_token_usage')->where('id', $row->id)->update($values);

                return;
            }

            $values = [
                'user_id'       => $userId,
                'usage_date'    => $today,
                'tokens_used'   => $tokensUsed,
                'request_count' => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if ($withCache) {
                $values['cached_tokens'] = $cachedTokens;
            }

            DB::table('ai_agent_token_usage')->insert($values);
        });
    }

    /**
     * Check once per process whether the additive cached_tokens migration ran.
     */
    protected function hasCachedTokensColumn(): bool
    {
        return self::$hasCachedTokensColumn ??= Schema::hasColumn('ai_agent_token_usage', 'cached_tokens');
    }
}
