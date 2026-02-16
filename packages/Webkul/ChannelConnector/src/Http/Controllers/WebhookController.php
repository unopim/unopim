<?php

namespace Webkul\ChannelConnector\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Events\WebhookReceived;
use Webkul\ChannelConnector\Jobs\ProcessWebhookJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Services\AdapterResolver;

class WebhookController extends Controller
{
    public function __construct(
        protected AdapterResolver $adapterResolver,
    ) {}

    public function receive(Request $request, string $token): JsonResponse
    {
        $maskedToken = substr($token, 0, 8).'...'.substr($token, -4);

        Log::info('[ChannelConnector] Incoming webhook received', [
            'token'      => $maskedToken,
            'event_type' => $request->input('event') ?? $request->input('type') ?? 'unknown',
            'ip'         => $request->ip(),
        ]);

        // 1. Find connector by webhook_token in settings JSON
        $connector = ChannelConnector::whereJsonContains('settings->webhook_token', $token)->first();

        if (! $connector) {
            Log::warning('[ChannelConnector] Webhook received with unknown token', [
                'token' => $maskedToken,
            ]);

            return response()->json(['error' => 'Unknown webhook token.'], 404);
        }

        // 2. Resolve adapter and verify webhook HMAC signature
        try {
            $adapter = $this->adapterResolver->resolve($connector);
        } catch (\Exception $e) {
            Log::error('[ChannelConnector] Webhook adapter resolution failed', [
                'connector_id' => $connector->id,
                'error'        => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Adapter resolution failed.'], 500);
        }

        if (! $adapter->verifyWebhook($request)) {
            Log::warning('[ChannelConnector] Webhook verification failed', [
                'connector_id' => $connector->id,
                'token'        => $maskedToken,
                'ip'           => $request->ip(),
            ]);

            return response()->json([
                'error' => trans('channel_connector::app.errors.CHN-050'),
            ], 401);
        }

        Log::debug('[ChannelConnector] Webhook verification successful', [
            'connector_id' => $connector->id,
        ]);

        // 3. Parse payload
        $payload = $request->json()->all();

        if (empty($payload)) {
            Log::warning('[ChannelConnector] Webhook received with empty payload', [
                'connector_id' => $connector->id,
            ]);

            return response()->json([
                'error' => trans('channel_connector::app.errors.CHN-051'),
            ], 400);
        }

        // 4. Dispatch ProcessWebhookJob to queue (async processing)
        $webhookEventId = $payload['id'] ?? $payload['event_id'] ?? null;
        ProcessWebhookJob::dispatch($connector->id, $payload, $webhookEventId)->onQueue('webhooks');

        Log::info('[ChannelConnector] Webhook job dispatched', [
            'connector_id' => $connector->id,
            'event_type'   => $payload['event'] ?? $payload['type'] ?? 'unknown',
        ]);

        // 5. Fire event and return 200 immediately (within 2 seconds per SC-008)
        event(new WebhookReceived($connector, $payload));

        return response()->json(['status' => 'acknowledged'], 200);
    }
}
