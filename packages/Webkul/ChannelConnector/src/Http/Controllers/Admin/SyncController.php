<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\ChannelConnector\Repositories\ChannelSyncJobRepository;
use Webkul\ChannelConnector\Services\SyncEngine;
use Webkul\ChannelConnector\Services\SyncJobManager;
use Webkul\Product\Models\Product;

class SyncController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected ChannelSyncJobRepository $syncJobRepository,
        protected ChannelFieldMappingRepository $mappingRepository,
        protected SyncJobManager $syncJobManager,
        protected SyncEngine $syncEngine,
    ) {}

    public function index(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.sync.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $jobs = $this->syncJobRepository->scopeQuery(
            fn ($q) => $q->where('channel_connector_id', $connector->id)->orderBy('created_at', 'desc')
        )->paginate(20);

        return view('channel_connector::admin.sync.index', compact('connector', 'jobs'));
    }

    public function trigger(Request $request, string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.sync.create')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $request->validate([
            'sync_type'      => ['required', Rule::in(['full', 'incremental', 'single'])],
            'product_codes'  => ['nullable', 'array'],
            'locales'        => ['nullable', 'array'],
        ]);

        try {
            $this->syncJobManager->triggerSync(
                $connector,
                $request->input('sync_type'),
                $request->input('product_codes', []),
                $request->input('locales', []),
            );

            session()->flash('success', trans('channel_connector::app.sync.trigger-success'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['sync' => $e->getMessage()]);
        }

        return redirect()->route('admin.channel_connector.sync.index', $code);
    }

    public function show(string $code, string $jobId)
    {
        if (! bouncer()->hasPermission('channel_connector.sync.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $job = $this->syncJobRepository->findOneByField('job_id', $jobId);

        if (! $job || $job->channel_connector_id !== $connector->id) {
            abort(404);
        }

        return view('channel_connector::admin.sync.show', compact('connector', 'job'));
    }

    public function preview(Request $request, string $code): JsonResponse
    {
        if (! bouncer()->hasPermission('channel_connector.sync.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $request->validate([
            'sync_type'     => ['nullable', Rule::in(['full', 'incremental'])],
            'product_codes' => ['nullable', 'array'],
            'limit'         => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $mappings = $this->mappingRepository->findWhere(['channel_connector_id' => $connector->id]);

        if ($mappings->isEmpty()) {
            return response()->json(['error' => 'No field mappings configured'], 422);
        }

        $limit = $request->input('limit', 5);
        $query = Product::query();

        if (! empty($request->input('product_codes'))) {
            $query->whereIn('sku', $request->input('product_codes'));
        }

        $syncType = $request->input('sync_type', 'full');

        if ($syncType === 'incremental' && $connector->last_synced_at) {
            $query->where('updated_at', '>', $connector->last_synced_at);
        }

        $products = $query->limit($limit)->get();
        $previews = [];

        foreach ($products as $product) {
            $payload = $this->syncEngine->prepareSyncPayload($product, $mappings);

            $previews[] = [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'payload'    => $payload,
                'data_hash'  => $this->syncEngine->computeDataHash($payload),
            ];
        }

        return response()->json([
            'total_available' => $query->count(),
            'previewed'       => count($previews),
            'products'        => $previews,
        ]);
    }
}
