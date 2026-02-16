<?php

namespace Webkul\ChannelConnector\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository;
use Webkul\ChannelConnector\Services\ConflictResolver;

class ConflictApiController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected ChannelSyncConflictRepository $conflictRepository,
        protected ConflictResolver $conflictResolver,
    ) {}

    /**
     * List conflicts for a specific connector with filtering.
     */
    public function index(Request $request, string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $query = $this->conflictRepository->scopeQuery(function ($q) use ($connector, $request) {
            $q = $q->where('channel_connector_id', $connector->id);

            if ($status = $request->get('resolution_status')) {
                $q = $q->where('resolution_status', $status);
            }

            if ($conflictType = $request->get('conflict_type')) {
                $q = $q->where('conflict_type', $conflictType);
            }

            if ($productId = $request->get('product_id')) {
                $q = $q->where('product_id', $productId);
            }

            return $q->orderBy('created_at', 'desc');
        });

        $limit = min((int) $request->get('limit', 10), 100);

        $conflicts = $query->paginate($limit);

        $conflicts->getCollection()->transform(fn ($conflict) => $this->formatConflict($conflict));

        return response()->json($conflicts);
    }

    /**
     * Show conflict detail with conflicting_fields structure.
     */
    public function show(string $code, int $id): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $conflict = $this->conflictRepository->find($id);

        if (! $conflict || $conflict->channel_connector_id !== $connector->id) {
            return response()->json(['message' => 'Conflict not found.'], 404);
        }

        $conflict->load(['product', 'syncJob', 'resolvedBy']);

        return response()->json([
            'data' => $this->formatConflictDetail($conflict),
        ]);
    }

    /**
     * Resolve a conflict via API.
     */
    public function resolve(Request $request, string $code, int $id): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $conflict = $this->conflictRepository->find($id);

        if (! $conflict || $conflict->channel_connector_id !== $connector->id) {
            return response()->json(['message' => 'Conflict not found.'], 404);
        }

        if ($conflict->resolution_status !== 'unresolved') {
            return response()->json([
                'message'           => 'Conflict is already resolved.',
                'resolution_status' => $conflict->resolution_status,
            ], 422);
        }

        $request->validate([
            'resolution'        => ['required', Rule::in(['pim_wins', 'channel_wins', 'merged', 'dismissed'])],
            'field_overrides'   => ['nullable', 'array'],
            'field_overrides.*' => [Rule::in(['pim', 'channel'])],
        ]);

        try {
            $this->conflictResolver->resolveConflict(
                $conflict,
                $request->input('resolution'),
                $request->input('field_overrides'),
            );

            return response()->json([
                'message' => trans('channel_connector::app.conflicts.resolve-success'),
                'data'    => $this->formatConflictDetail($conflict->fresh()),
            ]);
        } catch (\Exception $e) {
            \Log::error('[ChannelConnector] Conflict resolution failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => trans('channel_connector::app.conflicts.resolve-failed'),
            ], 500);
        }
    }

    /**
     * Format conflict for list view.
     */
    protected function formatConflict($conflict): array
    {
        return [
            'id'                  => $conflict->id,
            'product_id'          => $conflict->product_id,
            'conflict_type'       => $conflict->conflict_type,
            'resolution_status'   => $conflict->resolution_status,
            'pim_modified_at'     => $conflict->pim_modified_at?->toIso8601String(),
            'channel_modified_at' => $conflict->channel_modified_at?->toIso8601String(),
            'resolved_at'         => $conflict->resolved_at?->toIso8601String(),
            'created_at'          => $conflict->created_at->toIso8601String(),
        ];
    }

    /**
     * Format conflict with full detail including conflicting_fields.
     */
    protected function formatConflictDetail($conflict): array
    {
        return [
            'id'                  => $conflict->id,
            'product_id'          => $conflict->product_id,
            'product_sku'         => $conflict->product?->sku,
            'connector_name'      => $conflict->connector?->name,
            'conflict_type'       => $conflict->conflict_type,
            'conflicting_fields'  => $conflict->conflicting_fields,
            'resolution_status'   => $conflict->resolution_status,
            'resolution_details'  => $conflict->resolution_details,
            'pim_modified_at'     => $conflict->pim_modified_at?->toIso8601String(),
            'channel_modified_at' => $conflict->channel_modified_at?->toIso8601String(),
            'resolved_by'         => $conflict->resolvedBy?->name,
            'resolved_at'         => $conflict->resolved_at?->toIso8601String(),
            'sync_job_id'         => $conflict->channel_sync_job_id,
            'created_at'          => $conflict->created_at->toIso8601String(),
        ];
    }
}
