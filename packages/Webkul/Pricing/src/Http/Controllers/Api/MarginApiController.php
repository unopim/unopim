<?php

namespace Webkul\Pricing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Pricing\Events\MarginApproved;
use Webkul\Pricing\Repositories\MarginProtectionEventRepository;

class MarginApiController extends Controller
{
    public function __construct(
        protected MarginProtectionEventRepository $marginEventRepository,
    ) {}

    /**
     * GET margin events with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.margins.view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $query = $this->marginEventRepository->scopeQuery(function ($q) use ($request) {
            if ($eventType = $request->get('event_type')) {
                $q = $q->where('event_type', $eventType);
            }

            if ($productId = $request->get('product_id')) {
                $q = $q->where('product_id', $productId);
            }

            if ($channelId = $request->get('channel_id')) {
                $q = $q->where('channel_id', $channelId);
            }

            if ($request->boolean('pending_only', false)) {
                $q = $q->where('event_type', 'blocked')
                    ->whereNull('approved_by')
                    ->where(function ($sub) {
                        $sub->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            }

            if ($from = $request->get('from_date')) {
                $q = $q->where('created_at', '>=', $from);
            }

            if ($to = $request->get('to_date')) {
                $q = $q->where('created_at', '<=', $to);
            }

            return $q->orderBy('created_at', 'desc');
        });

        $limit = min((int) $request->get('limit', 10), 100);
        $events = $query->paginate($limit);

        $events->getCollection()->transform(fn ($event) => $this->formatEvent($event));

        return response()->json($events);
    }

    /**
     * POST approve a margin protection event.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.margins.approve')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $marginEvent = $this->marginEventRepository->find($id);

        if (! $marginEvent) {
            return response()->json(['message' => 'Margin event not found.'], 404);
        }

        if (! $marginEvent->isPending()) {
            return response()->json([
                'message'    => 'This margin event is not pending approval.',
                'event_type' => $marginEvent->event_type,
            ], 422);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $adminId = auth()->guard('admin')->id();

        $this->marginEventRepository->update([
            'event_type'  => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'reason'      => $request->input('reason'),
        ], $id);

        $marginEvent = $marginEvent->fresh();

        event(new MarginApproved($marginEvent, $adminId));

        return response()->json([
            'message' => trans('pricing::app.margins.approve-success'),
            'data'    => $this->formatEvent($marginEvent),
        ]);
    }

    /**
     * Format a margin event for API response.
     */
    protected function formatEvent($event): array
    {
        return [
            'id'                        => $event->id,
            'product_id'                => $event->product_id,
            'channel_id'                => $event->channel_id,
            'event_type'                => $event->event_type,
            'proposed_price'            => (float) $event->proposed_price,
            'break_even_price'          => (float) $event->break_even_price,
            'minimum_margin_price'      => (float) $event->minimum_margin_price,
            'target_margin_price'       => $event->target_margin_price ? (float) $event->target_margin_price : null,
            'currency_code'             => $event->currency_code,
            'margin_percentage'         => (float) $event->margin_percentage,
            'minimum_margin_percentage' => (float) $event->minimum_margin_percentage,
            'reason'                    => $event->reason,
            'approved_by'               => $event->approved_by,
            'approved_at'               => $event->approved_at?->toIso8601String(),
            'expires_at'                => $event->expires_at?->toIso8601String(),
            'created_at'                => $event->created_at->toIso8601String(),
            'updated_at'                => $event->updated_at->toIso8601String(),
        ];
    }
}
