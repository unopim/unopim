<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Services\AdapterResolver;

class ConnectionTestController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected AdapterResolver $adapterResolver,
    ) {}

    public function test(string $code): JsonResponse
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 401);
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json([
                'success' => false,
                'message' => 'Connector not found.',
            ], 404);
        }

        try {
            $adapter = $this->adapterResolver->resolve($connector);
            $result = $adapter->testConnection($connector->credentials ?? []);

            if ($result->success) {
                $connector->update(['status' => 'connected']);
            }

            return response()->json($result->toArray());
        } catch (\Exception $e) {
            \Log::error('[ChannelConnector] Connection test failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => trans('channel_connector::app.connectors.test-failed'),
                'errors'  => [trans('channel_connector::app.connectors.test-failed')],
            ], 500);
        }
    }
}
