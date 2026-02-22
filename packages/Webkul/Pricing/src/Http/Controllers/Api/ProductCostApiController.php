<?php

namespace Webkul\Pricing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Webkul\Pricing\Events\CostUpdated;
use Webkul\Pricing\Repositories\ProductCostRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductCostApiController extends Controller
{
    public function __construct(
        protected ProductCostRepository $productCostRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * GET costs for a product by its SKU code.
     */
    public function index(Request $request, string $productCode): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.costs.view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $product = $this->productRepository->findOneByField('sku', $productCode);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $query = $this->productCostRepository->scopeQuery(function ($q) use ($product, $request) {
            $q = $q->where('product_id', $product->id);

            if ($costType = $request->get('cost_type')) {
                $q = $q->where('cost_type', $costType);
            }

            if ($request->boolean('active_only', false)) {
                $today = now()->toDateString();
                $q = $q->where('effective_from', '<=', $today)
                    ->where(function ($sub) use ($today) {
                        $sub->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $today);
                    });
            }

            return $q->orderBy('effective_from', 'desc');
        });

        $limit = min((int) $request->get('limit', 10), 100);
        $costs = $query->paginate($limit);

        $costs->getCollection()->transform(fn ($cost) => $this->formatCost($cost));

        return response()->json($costs);
    }

    /**
     * POST create a new cost entry for a product.
     */
    public function store(Request $request, string $productCode): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.costs.create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $product = $this->productRepository->findOneByField('sku', $productCode);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $request->validate([
            'cost_type'      => ['required', Rule::in(['cogs', 'operational', 'marketing', 'platform', 'shipping', 'overhead'])],
            'amount'         => ['required', 'numeric', 'min:0'],
            'currency_code'  => ['required', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $data = $request->only(['cost_type', 'amount', 'currency_code', 'effective_from', 'effective_to', 'notes']);
        $data['product_id'] = $product->id;
        $data['created_by'] = auth()->guard('admin')->id();

        $cost = $this->productCostRepository->create($data);

        event(new CostUpdated($cost, 0));

        return response()->json($this->formatCost($cost), 201);
    }

    /**
     * PUT/PATCH update an existing cost entry for a product.
     */
    public function update(Request $request, string $productCode, int $costId): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.costs.edit')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $product = $this->productRepository->findOneByField('sku', $productCode);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $cost = $this->productCostRepository->find($costId);

        if (! $cost || $cost->product_id !== $product->id) {
            return response()->json(['message' => 'Cost entry not found.'], 404);
        }

        $request->validate([
            'cost_type'      => ['sometimes', Rule::in(['cogs', 'operational', 'marketing', 'platform', 'shipping', 'overhead'])],
            'amount'         => ['sometimes', 'numeric', 'min:0'],
            'currency_code'  => ['sometimes', 'string', 'size:3'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        // Optimistic locking check (F-003)
        if ($request->has('version') && (int) $request->input('version') !== $cost->version) {
            return response()->json(['message' => 'This record has been modified by another user. Please reload and try again.'], 409);
        }

        return DB::transaction(function () use ($request, $cost, $costId) {
            $previousAmount = (float) $cost->amount;
            $data = $request->only(['cost_type', 'amount', 'currency_code', 'effective_from', 'effective_to', 'notes']);
            $data['version'] = $cost->version + 1;

            $cost = $this->productCostRepository->update($data, $costId);

            event(new CostUpdated($cost, $previousAmount));

            return response()->json($this->formatCost($cost));
        });
    }

    /**
     * Format a cost entry for API response.
     */
    protected function formatCost($cost): array
    {
        return [
            'id'             => $cost->id,
            'product_id'     => $cost->product_id,
            'cost_type'      => $cost->cost_type,
            'amount'         => (float) $cost->amount,
            'currency_code'  => $cost->currency_code,
            'effective_from' => $cost->effective_from?->toDateString(),
            'effective_to'   => $cost->effective_to?->toDateString(),
            'notes'          => $cost->notes,
            'created_by'     => $cost->created_by,
            'created_at'     => $cost->created_at->toIso8601String(),
            'updated_at'     => $cost->updated_at->toIso8601String(),
        ];
    }
}
