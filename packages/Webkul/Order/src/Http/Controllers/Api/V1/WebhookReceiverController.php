<?php

namespace Webkul\Order\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\AdminApi\Http\Controllers\V1\Controller;
use Webkul\Order\Events\WebhookReceived;
use Webkul\Order\Http\Requests\WebhookReceiveRequest;
use Webkul\Order\Services\WebhookVerifier;

/**
 * Webhook Receiver Controller
 *
 * Receives and processes webhook events from external channels
 * with HMAC signature verification and rate limiting.
 */
class WebhookReceiverController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  WebhookVerifier  $webhookVerifier
     * @return void
     */
    public function __construct(protected WebhookVerifier $webhookVerifier)
    {
    }

    /**
     * Generic webhook receiver.
     *
     * @param  WebhookReceiveRequest  $request
     * @param  string  $channelCode
     * @return JsonResponse
     */
    public function receive(WebhookReceiveRequest $request, string $channelCode): JsonResponse
    {
        try {
            // Verify webhook signature
            if (! $this->webhookVerifier->verify($request, $channelCode)) {
                Log::warning('Webhook signature verification failed', [
                    'channel' => $channelCode,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Invalid signature',
                ], 401);
            }

            // Dispatch event for processing
            Event::dispatch(new WebhookReceived(
                $channelCode,
                $request->all(),
                $request->header('X-Event-Type')
            ));

            return response()->json([
                'message' => 'Webhook received successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'channel' => $channelCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Salla-specific webhook endpoint.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function salla(Request $request): JsonResponse
    {
        try {
            // Salla sends signature in X-Salla-Signature header
            $signature = $request->header('X-Salla-Signature');
            $eventType = $request->input('event');

            if (! $this->webhookVerifier->verifySalla($request->getContent(), $signature)) {
                Log::warning('Salla webhook signature verification failed', [
                    'event' => $eventType,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'message' => 'Invalid signature',
                ], 401);
            }

            Event::dispatch(new WebhookReceived('salla', $request->all(), $eventType));

            return response()->json([
                'message' => 'Webhook received',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Salla webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Processing failed',
            ], 500);
        }
    }

    /**
     * Shopify-specific webhook endpoint.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function shopify(Request $request): JsonResponse
    {
        try {
            // Shopify sends HMAC in X-Shopify-Hmac-SHA256 header
            $hmac = $request->header('X-Shopify-Hmac-SHA256');
            $topic = $request->header('X-Shopify-Topic');

            if (! $this->webhookVerifier->verifyShopify($request->getContent(), $hmac)) {
                Log::warning('Shopify webhook HMAC verification failed', [
                    'topic' => $topic,
                    'shop' => $request->header('X-Shopify-Shop-Domain'),
                ]);

                return response()->json([
                    'message' => 'Invalid HMAC',
                ], 401);
            }

            Event::dispatch(new WebhookReceived('shopify', $request->all(), $topic));

            return response()->json([
                'message' => 'Webhook received',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Shopify webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Processing failed',
            ], 500);
        }
    }

    /**
     * WooCommerce-specific webhook endpoint.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function woocommerce(Request $request): JsonResponse
    {
        try {
            // WooCommerce sends signature in X-WC-Webhook-Signature header
            $signature = $request->header('X-WC-Webhook-Signature');
            $topic = $request->header('X-WC-Webhook-Topic');

            if (! $this->webhookVerifier->verifyWooCommerce($request->getContent(), $signature)) {
                Log::warning('WooCommerce webhook signature verification failed', [
                    'topic' => $topic,
                    'source' => $request->header('X-WC-Webhook-Source'),
                ]);

                return response()->json([
                    'message' => 'Invalid signature',
                ], 401);
            }

            Event::dispatch(new WebhookReceived('woocommerce', $request->all(), $topic));

            return response()->json([
                'message' => 'Webhook received',
            ], 200);
        } catch (\Exception $e) {
            Log::error('WooCommerce webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Processing failed',
            ], 500);
        }
    }
}
