<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Manages the approval queue for AI-generated changes.
 *
 * When approval_mode=review, write tools queue changes as pending
 * changesets instead of applying them. This controller lets admins
 * review, approve, or reject those changes.
 */
class ApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! bouncer()->hasPermission('ai-agent.approvals')) {
                abort(401, trans('ai-agent::app.common.unauthorized'));
            }

            return $next($request);
        });
    }

    /**
     * List pending changesets awaiting approval.
     */
    public function index(): JsonResponse
    {
        $pending = DB::table('ai_agent_changesets as c')
            ->leftJoin('admins as a', 'a.id', '=', 'c.user_id')
            ->where('c.status', 'pending')
            ->orderByDesc('c.created_at')
            ->select(
                'c.id', 'c.description', 'c.affected_count',
                'c.changes', 'c.created_at',
                'a.name as requested_by',
            )
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $row->changes = json_decode($row->changes, true);

                return $row;
            });

        return new JsonResponse(['pending' => $pending]);
    }

    /**
     * Approve and apply a pending changeset.
     */
    public function approve(int $id): JsonResponse
    {
        $changeset = DB::table('ai_agent_changesets')
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (! $changeset) {
            return new JsonResponse(['error' => 'Changeset not found or already processed'], 404);
        }

        $changes = json_decode($changeset->changes, true) ?? [];
        $applied = 0;

        // Apply the queued change based on its type
        $type = $changes['type'] ?? 'unknown';
        $repo = app('Webkul\Product\Repositories\ProductRepository');

        if ($type === 'create_product' && ! empty($changes['data'])) {
            $product = $repo->create($changes['data']['create'] ?? []);
            if ($product && ! empty($changes['data']['values'])) {
                $repo->updateWithValues(['values' => $changes['data']['values']], $product->id);
            }
            $applied = 1;
        } elseif ($type === 'update_product' && ! empty($changes['product_id'])) {
            $repo->updateWithValues(
                ['values' => $changes['data']['values'] ?? []],
                $changes['product_id'],
            );
            $applied = 1;
        } elseif ($type === 'delete_products' && ! empty($changes['product_ids'])) {
            foreach ($changes['product_ids'] as $pid) {
                $repo->delete($pid);
                $applied++;
            }
        } elseif ($type === 'bulk_edit' && ! empty($changes['product_updates'])) {
            foreach ($changes['product_updates'] as $update) {
                $repo->updateWithValues(
                    ['values' => $update['values']],
                    $update['product_id'],
                );
                $applied++;
            }
        } elseif ($type === 'assign_categories' && ! empty($changes['product_updates'])) {
            foreach ($changes['product_updates'] as $update) {
                $repo->updateWithValues(
                    ['values' => $update['values']],
                    $update['product_id'],
                );
                $applied++;
            }
        }

        DB::table('ai_agent_changesets')->where('id', $id)->update([
            'status'         => 'applied',
            'affected_count' => $applied,
            'applied_at'     => now(),
            'updated_at'     => now(),
        ]);

        return new JsonResponse([
            'approved' => true,
            'applied'  => $applied,
        ]);
    }

    /**
     * Reject a pending changeset.
     */
    public function reject(int $id): JsonResponse
    {
        $updated = DB::table('ai_agent_changesets')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update([
                'status'     => 'rolled_back',
                'updated_at' => now(),
            ]);

        return new JsonResponse(['rejected' => (bool) $updated]);
    }
}
