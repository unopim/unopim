<?php

namespace Webkul\Webhook\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Webhook\Models\WebhookSetting;
use Webkul\Webhook\Repositories\SettingsRepository;

class SettingsController
{
    public function __construct(
        protected SettingsRepository $settingsRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('webhook::settings.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $active = (int) $request->get('webhook_active', '0');

        $settings = ['webhook_active' => $active];

        if ($active) {
            $settings['webhook_url'] = $request->filled('webhook_url') ? $request->webhook_url : null;
        }

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
            'message' => trans('webhook::app.components.layouts.sidebar.menu.webhook.submenu.settings.success'),
        ]);
    }

    public function listSettings(): JsonResponse
    {
        return response()->json([
            'data' => $this->settingsRepository->getAllDataAndNormalize(),
        ]);
    }

    public function listHistory()
    {
        return view('webhook::settings.history');
    }
}
