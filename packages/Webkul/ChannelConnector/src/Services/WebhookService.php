<?php

namespace Webkul\ChannelConnector\Services;

use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;

class WebhookService
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected AdapterResolver $adapterResolver,
    ) {}

    public function generateWebhookToken(): string
    {
        $token = bin2hex(random_bytes(32));

        Log::info('[ChannelConnector] Webhook token generated', [
            'token_prefix' => substr($token, 0, 8).'...',
        ]);

        return $token;
    }

    public function registerWebhooks(ChannelConnector $connector): bool
    {
        $settings = $connector->settings ?? [];
        $events = $settings['webhook_events'] ?? [];
        $token = $settings['webhook_token'] ?? null;

        if (empty($events) || empty($token)) {
            Log::warning('[ChannelConnector] Webhook registration skipped: missing events or token', [
                'connector_id' => $connector->id,
                'has_events'   => ! empty($events),
                'has_token'    => ! empty($token),
            ]);

            return false;
        }

        Log::info('[ChannelConnector] Registering webhooks', [
            'connector_id' => $connector->id,
            'events'       => $events,
        ]);

        $adapter = $this->adapterResolver->resolve($connector);
        $callbackUrl = route('channel_connector.webhooks.receive', $token);

        $result = $adapter->registerWebhooks($events, $callbackUrl);

        Log::info('[ChannelConnector] Webhook registration result', [
            'connector_id' => $connector->id,
            'success'      => $result,
        ]);

        return $result;
    }

    public function unregisterWebhooks(ChannelConnector $connector): bool
    {
        Log::info('[ChannelConnector] Unregistering webhooks', [
            'connector_id' => $connector->id,
        ]);

        try {
            $adapter = $this->adapterResolver->resolve($connector);

            $result = $adapter->registerWebhooks([], '');

            Log::info('[ChannelConnector] Webhook unregistration result', [
                'connector_id' => $connector->id,
                'success'      => $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('[ChannelConnector] Webhook unregistration failed', [
                'connector_id' => $connector->id,
                'error'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getCallbackUrl(ChannelConnector $connector): ?string
    {
        $token = ($connector->settings ?? [])['webhook_token'] ?? null;

        return $token ? route('channel_connector.webhooks.receive', $token) : null;
    }

    public function ensureWebhookToken(ChannelConnector $connector): string
    {
        $settings = $connector->settings ?? [];

        if (empty($settings['webhook_token'])) {
            $settings['webhook_token'] = $this->generateWebhookToken();
            $this->connectorRepository->update(['settings' => $settings], $connector->id);
            $connector->refresh();

            Log::info('[ChannelConnector] Webhook token created for connector', [
                'connector_id' => $connector->id,
                'token_prefix' => substr($settings['webhook_token'], 0, 8).'...',
            ]);
        }

        return $settings['webhook_token'];
    }
}
