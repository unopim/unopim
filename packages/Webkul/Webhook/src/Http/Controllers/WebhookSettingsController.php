<?php

namespace Webkul\Webhook\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Webkul\Webhook\Models\WebhookSetting;
use Webkul\Webhook\Repositories\SettingsRepository;

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
    public function index()
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
            'webhook_active' => 'sometimes|in:0,1,true,false',
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

        $active = (int) $request->input('webhook_active', '0');
        $url = $request->filled('webhook_url') ? $request->webhook_url : null;

        if ($active === 1 && ! empty($url)) {
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
            $response = Http::timeout(self::TEST_REQUEST_TIMEOUT)->post($url, $payload);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => trans('webhook::app.configuration.webhook.settings.index.webhook_url.connection_failed', [
                    'error' => $e->getMessage(),
                ]),
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
