<?php

namespace Webkul\Pricing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\Pricing\Repositories\PricingStrategyRepository;

class StrategyApiController extends Controller
{
    public function __construct(
        protected PricingStrategyRepository $strategyRepository,
    ) {}

    /**
     * GET pricing strategies with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.strategies.view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $query = $this->strategyRepository->scopeQuery(function ($q) use ($request) {
            if ($scopeType = $request->get('scope_type')) {
                $q = $q->where('scope_type', $scopeType);
            }

            if ($request->has('is_active')) {
                $q = $q->where('is_active', $request->boolean('is_active'));
            }

            if ($scopeId = $request->get('scope_id')) {
                $q = $q->where('scope_id', $scopeId);
            }

            return $q->orderByDesc('priority')->orderBy('scope_type');
        });

        $limit = min((int) $request->get('limit', 10), 100);
        $strategies = $query->paginate($limit);

        $strategies->getCollection()->transform(fn ($strategy) => $this->formatStrategy($strategy));

        return response()->json($strategies);
    }

    /**
     * POST create a new pricing strategy.
     */
    public function store(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.strategies.create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $request->validate([
            'scope_type'                => ['required', Rule::in(['global', 'category', 'channel', 'product'])],
            'scope_id'                  => ['nullable', 'integer'],
            'minimum_margin_percentage' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'target_margin_percentage'  => ['required', 'numeric', 'min:0', 'max:99.99'],
            'premium_margin_percentage' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'psychological_pricing'     => ['sometimes', 'boolean'],
            'round_to'                  => ['sometimes', Rule::in(['0.99', '0.95', '0.00', 'none'])],
            'is_active'                 => ['sometimes', 'boolean'],
            'priority'                  => ['sometimes', 'integer', 'min:0', 'max:255'],
        ]);

        $data = $request->only([
            'scope_type', 'scope_id',
            'minimum_margin_percentage', 'target_margin_percentage', 'premium_margin_percentage',
            'psychological_pricing', 'round_to', 'is_active', 'priority',
        ]);
        // tenant_id is set automatically by BelongsToTenant trait

        // Validate margin ordering
        $min = (float) ($data['minimum_margin_percentage'] ?? 0);
        $target = (float) ($data['target_margin_percentage'] ?? 0);
        $premium = (float) ($data['premium_margin_percentage'] ?? 0);

        if ($min >= $target) {
            return response()->json([
                'message' => 'Minimum margin must be less than target margin.',
                'errors'  => ['minimum_margin_percentage' => ['Minimum margin must be less than target margin.']],
            ], 422);
        }

        if ($target >= $premium) {
            return response()->json([
                'message' => 'Target margin must be less than premium margin.',
                'errors'  => ['target_margin_percentage' => ['Target margin must be less than premium margin.']],
            ], 422);
        }

        // Validate scope_id required for non-global types
        if ($data['scope_type'] !== 'global' && empty($data['scope_id'])) {
            return response()->json([
                'message' => 'scope_id is required when scope_type is not global.',
                'errors'  => ['scope_id' => ['scope_id is required when scope_type is not global.']],
            ], 422);
        }

        $strategy = $this->strategyRepository->create($data);

        return response()->json($this->formatStrategy($strategy), 201);
    }

    /**
     * Format a strategy for API response.
     */
    protected function formatStrategy($strategy): array
    {
        return [
            'id'                        => $strategy->id,
            'scope_type'                => $strategy->scope_type,
            'scope_id'                  => $strategy->scope_id,
            'minimum_margin_percentage' => (float) $strategy->minimum_margin_percentage,
            'target_margin_percentage'  => (float) $strategy->target_margin_percentage,
            'premium_margin_percentage' => (float) $strategy->premium_margin_percentage,
            'psychological_pricing'     => (bool) $strategy->psychological_pricing,
            'round_to'                  => $strategy->round_to,
            'is_active'                 => (bool) $strategy->is_active,
            'priority'                  => (int) $strategy->priority,
            'created_at'                => $strategy->created_at->toIso8601String(),
            'updated_at'                => $strategy->updated_at->toIso8601String(),
        ];
    }
}
