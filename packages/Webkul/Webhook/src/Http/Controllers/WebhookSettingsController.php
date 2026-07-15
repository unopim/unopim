<?php

namespace Webkul\Webhook\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Webkul\Webhook\Models\WebhookSetting;
use Webkul\Webhook\Repositories\SettingsRepository;
use Webkul\Webhook\Validators\SafeWebhookUrl;

class WebhookSettingsController
{
    protected const TEST_REQUEST_TIMEOUT = 10;

    public function __construct(
        protected SettingsRepository $settingsRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): Factory|\Illuminate\Contracts\View\View
    {
        return view('webhook::settings.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'webhook_active' => ['sometimes', 'in:0,1,true,false'],
            'webhook_url'    => [
                'nullable',
                'required_if:webhook_active,1,true',
                'url',
                'regex:#^https?://#i',
                'max:2048',
            ],
        ], [
            'webhook_url.required_if' => trans('webhook::app.configuration.webhook.settings.index.webhook_url.required'),
            'webhook_url.regex'       => trans('webhook::app.configuration.webhook.settings.index.webhook_url.scheme'),
        ]);

        // Cast 'true'/'false' strings + 0/1 alike — (int) 'true' === 0 would
        // silently disarm the activation path and skip the SSRF probe below.
        $active = filter_var($request->input('webhook_active', false), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $url = $request->filled('webhook_url') ? $request->webhook_url : null;

        if ($active === 1 && ! empty($url)) {
            // SSRF guard (CWE-918): reject URLs that resolve to loopback,
            // private, link-local, multicast or otherwise reserved address
            // space — including cloud metadata 169.254.169.254 — BEFORE the
            // synchronous test POST is fired against the supplied host.
            $safety = SafeWebhookUrl::validate($url);

            if (! $safety['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => trans('webhook::app.configuration.webhook.settings.index.webhook_url.unsafe'),
                    'errors'  => [
                        'webhook_url' => [trans('webhook::app.configuration.webhook.settings.index.webhook_url.unsafe')],
                    ],
                ], 422);
            }

            $testResult = $this->testWebhookUrl($url);

            if (! $testResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $testResult['message'],
                    'errors'  => [
                        'webhook_url' => [$testResult['message']],
                    ],
                ], 422);
            }
        }

        $settings = [
            'webhook_active' => $active,
            'webhook_url'    => $url,
        ];

        WebhookSetting::$auditingDisabled = true;

        $setting = null;

        $oldSettings = $this->settingsRepository->getAllDataAndNormalize();

        foreach ($settings as $field => $value) {
            $setting = $this->settingsRepository->createOrUpdate($field, $value);
        }

        WebhookSetting::$auditingDisabled = false;

        Event::dispatch('core.model.proxy.sync.webhookSettings', [
            'old_values' => $oldSettings,
            'new_values' => $settings,
            'model'      => $setting,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $setting,
            'message' => trans('webhook::app.configuration.webhook.settings.index.success'),
        ]);
    }

    public function listSettings(): JsonResponse
    {
        return response()->json([
            'data' => $this->settingsRepository->getAllDataAndNormalize(),
        ]);
    }

    /**
     * Probe the webhook URL with a test payload before persisting the settings.
     * Connection errors and non-2xx responses are treated as invalid.
     */
    protected function testWebhookUrl(string $url): array
    {
        $payload = [
            'event'     => 'webhook.test',
            'timestamp' => now()->toDateTimeString(),
            'message'   => 'Unopim webhook URL validation test',
        ];

        try {
            // Pin the request to the validated IP (CURLOPT_RESOLVE) and
            // disable redirect-following — closes the DNS-rebinding TOCTOU
            // gap and the redirect-chain bypass.
            $response = Http::withOptions(SafeWebhookUrl::httpOptions($url))
                ->timeout(self::TEST_REQUEST_TIMEOUT)
                ->post($url, $payload);
        } catch (\Throwable) {
            return [
                'success' => false,
                'message' => trans('webhook::app.configuration.webhook.settings.index.webhook_url.connection_failed'),
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => trans('webhook::app.configuration.webhook.settings.index.webhook_url.unreachable', [
                    'code' => $response->status(),
                ]),
            ];
        }

        return ['success' => true, 'message' => ''];
    }
}
