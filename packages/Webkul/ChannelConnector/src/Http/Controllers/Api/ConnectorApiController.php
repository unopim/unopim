<?php

namespace Webkul\ChannelConnector\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\Events\ConnectorCreated;
use Webkul\ChannelConnector\Events\ConnectorCreating;
use Webkul\ChannelConnector\Events\ConnectorDeleted;
use Webkul\ChannelConnector\Events\ConnectorDeleting;
use Webkul\ChannelConnector\Events\ConnectorUpdated;
use Webkul\ChannelConnector\Http\Requests\ConnectorRequest;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;

class ConnectorApiController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->connectorRepository->scopeQuery(function ($q) use ($request) {
            if ($type = $request->get('channel_type')) {
                $q = $q->where('channel_type', $type);
            }

            if ($status = $request->get('status')) {
                $q = $q->where('status', $status);
            }

            return $q->orderBy('created_at', 'desc');
        });

        $limit = min((int) $request->get('limit', 10), 100);
        $connectors = $query->paginate($limit);

        $connectors->getCollection()->transform(fn ($c) => $this->formatConnector($c));

        return response()->json($connectors);
    }

    public function show(string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        return response()->json(['data' => $this->formatConnector($connector)]);
    }

    public function store(ConnectorRequest $request): JsonResponse
    {
        $data = $request->validated();

        event(new ConnectorCreating($data));

        if (isset($data['credentials']) && is_array($data['credentials'])) {
            // Credentials will be encrypted by the model cast
        }

        $connector = $this->connectorRepository->create($data);

        event(new ConnectorCreated($connector));

        return response()->json($this->formatConnector($connector), 201);
    }

    public function update(ConnectorRequest $request, string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $data = $request->validated();
        $connector = $this->connectorRepository->update($data, $connector->id);

        event(new ConnectorUpdated($connector));

        return response()->json($this->formatConnector($connector));
    }

    public function destroy(string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        event(new ConnectorDeleting($connector));

        $this->connectorRepository->delete($connector->id);

        event(new ConnectorDeleted($connector));

        return response()->json([
            'message' => trans('channel_connector::app.connectors.delete-success'),
        ]);
    }

    protected function formatConnector($connector): array
    {
        $settings = $connector->settings ?? [];
        $redactedSettings = array_diff_key($settings, array_flip([
            'webhook_token', 'api_key', 'api_secret',
            'client_secret', 'access_token', 'refresh_token',
        ]));

        return [
            'code'           => $connector->code,
            'name'           => $connector->name,
            'channel_type'   => $connector->channel_type,
            'status'         => $connector->status,
            'settings'       => $redactedSettings,
            'last_synced_at' => $connector->last_synced_at?->toIso8601String(),
            'created_at'     => $connector->created_at->toIso8601String(),
            'updated_at'     => $connector->updated_at->toIso8601String(),
        ];
    }
}
