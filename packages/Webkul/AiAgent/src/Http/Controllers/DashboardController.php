<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * AI Agent analytics dashboard and audit trail.
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! bouncer()->hasPermission('ai-agent.dashboard')) {
                abort(401, trans('ai-agent::app.common.unauthorized'));
            }

            return $next($request);
        });
    }

    /**
     * Get agent usage analytics.
     */
    public function analytics(): JsonResponse
    {
        $today = now()->toDateString();
        $weekAgo = now()->subDays(7)->toDateString();

        // Token usage today
        $todayUsage = DB::table('ai_agent_token_usage')
            ->where('usage_date', $today)
            ->selectRaw('SUM(tokens_used) as tokens, SUM(request_count) as requests')
            ->first();

        // Token usage this week
        $weekUsage = DB::table('ai_agent_token_usage')
            ->where('usage_date', '>=', $weekAgo)
            ->selectRaw('SUM(tokens_used) as tokens, SUM(request_count) as requests')
            ->first();

        // Daily breakdown (last 7 days)
        $dailyBreakdown = DB::table('ai_agent_token_usage')
            ->where('usage_date', '>=', $weekAgo)
            ->selectRaw('usage_date, SUM(tokens_used) as tokens, SUM(request_count) as requests')
            ->groupBy('usage_date')
            ->orderBy('usage_date')
            ->get();

        // Recent tasks
        $recentTasks = DB::table('ai_agent_tasks')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'type', 'status', 'progress', 'created_at', 'completed_at']);

        // Budget info
        $budget = (int) core()->getConfigData('general.magic_ai.agentic_pim.daily_token_budget');

        return new JsonResponse([
            'today' => [
                'tokens'   => (int) ($todayUsage->tokens ?? 0),
                'requests' => (int) ($todayUsage->requests ?? 0),
            ],
            'week' => [
                'tokens'   => (int) ($weekUsage->tokens ?? 0),
                'requests' => (int) ($weekUsage->requests ?? 0),
            ],
            'daily_breakdown' => $dailyBreakdown,
            'budget'          => $budget > 0 ? $budget : null,
            'budget_used_pct' => $budget > 0 ? round(($todayUsage->tokens ?? 0) / $budget * 100, 1) : null,
            'recent_tasks'    => $recentTasks,
        ]);
    }

    /**
     * Get audit trail of AI agent actions.
     */
    public function auditTrail(): JsonResponse
    {
        $changesets = DB::table('ai_agent_changesets as c')
            ->leftJoin('admins as a', 'a.id', '=', 'c.user_id')
            ->select(
                'c.id', 'c.description', 'c.status', 'c.affected_count',
                'c.applied_at', 'c.rolled_back_at', 'c.created_at',
                'a.name as user_name',
            )
            ->orderByDesc('c.created_at')
            ->limit(50)
            ->get();

        return new JsonResponse(['changesets' => $changesets]);
    }

    /**
     * Rollback a changeset.
     */
    public function rollback(int $id): JsonResponse
    {
        $changeset = DB::table('ai_agent_changesets')
            ->where('id', $id)
            ->where('status', 'applied')
            ->first();

        if (! $changeset) {
            return new JsonResponse(['error' => 'Changeset not found or already rolled back'], 404);
        }

        $changes = json_decode($changeset->changes, true) ?? [];

        // Attempt to rollback product changes
        $rolledBack = 0;
        if (! empty($changes['product_id']) && ! empty($changes['previous_values'])) {
            $repo = app('Webkul\Product\Repositories\ProductRepository');
            $repo->updateWithValues(['values' => $changes['previous_values']], $changes['product_id']);
            $rolledBack++;
        }

        DB::table('ai_agent_changesets')
            ->where('id', $id)
            ->update([
                'status'         => 'rolled_back',
                'rolled_back_at' => now(),
                'updated_at'     => now(),
            ]);

        return new JsonResponse([
            'rolled_back' => true,
            'affected'    => $rolledBack,
        ]);
    }

    /**
     * Get pending notifications from quality scans and background agents.
     */
    public function notifications(): JsonResponse
    {
        $notifications = DB::table('ai_agent_tasks')
            ->where('type', 'notification')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'config', 'result', 'priority', 'created_at'])
            ->map(function ($n) {
                $config = json_decode($n->config, true) ?? [];

                return [
                    'id'       => $n->id,
                    'title'    => $config['title'] ?? 'AI Agent Notification',
                    'message'  => $config['message'] ?? '',
                    'action'   => $config['action'] ?? null,
                    'priority' => $n->priority,
                    'created'  => $n->created_at,
                ];
            });

        return new JsonResponse(['notifications' => $notifications]);
    }

    /**
     * Dismiss a notification.
     */
    public function dismissNotification(int $id): JsonResponse
    {
        DB::table('ai_agent_tasks')
            ->where('id', $id)
            ->where('type', 'notification')
            ->update(['status' => 'completed', 'updated_at' => now()]);

        return new JsonResponse(['dismissed' => true]);
    }
}
