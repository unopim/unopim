<?php

namespace Webkul\Order\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Channel\Repositories\ChannelRepository;
use Webkul\Order\DataGrids\Admin\WebhookDataGrid;
use Webkul\Order\Http\Requests\WebhookStoreRequest;
use Webkul\Order\Http\Requests\WebhookUpdateRequest;
use Webkul\Order\Repositories\WebhookRepository;

/**
 * Webhook Controller
 *
 * Manages webhook configurations for receiving order events
 * from external channels.
 */
class WebhookController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  WebhookRepository  $webhookRepository
     * @param  ChannelRepository  $channelRepository
     * @return void
     */
    public function __construct(
        protected WebhookRepository $webhookRepository,
        protected ChannelRepository $channelRepository
    ) {
    }

    /**
     * Display a listing of webhooks.
     *
     * @return View|JsonResponse
     */
    public function index(): View|JsonResponse
    {
        if (! bouncer()->allows('orders.webhooks.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        if (request()->ajax()) {
            return datagrid(WebhookDataGrid::class)->process();
        }

        return view('order::admin.webhooks.index');
    }

    /**
     * Show the form for creating a new webhook.
     *
     * @return View
     */
    public function create(): View
    {
        if (! bouncer()->allows('orders.webhooks.create')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $channels = $this->channelRepository->all();

        $eventTypes = config('order.webhook_events', [
            'order.created',
            'order.updated',
            'order.cancelled',
            'order.refunded',
            'order.fulfilled',
        ]);

        return view('order::admin.webhooks.create', [
            'channels' => $channels,
            'eventTypes' => $eventTypes,
        ]);
    }

    /**
     * Store a newly created webhook in storage.
     *
     * @param  WebhookStoreRequest  $request
     * @return RedirectResponse
     */
    public function store(WebhookStoreRequest $request): RedirectResponse
    {
        if (! bouncer()->allows('orders.webhooks.create')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');
        $data['secret_key'] = bin2hex(random_bytes(32));

        $this->webhookRepository->create($data);

        return redirect()->route('admin.orders.webhooks.index')
            ->with('success', trans('order::app.admin.webhooks.create-success'));
    }

    /**
     * Show the form for editing the specified webhook.
     *
     * @param  int  $id
     * @return View
     */
    public function edit(int $id): View
    {
        if (! bouncer()->allows('orders.webhooks.edit')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $webhook = $this->webhookRepository->findOrFail($id);
        $channels = $this->channelRepository->all();

        $eventTypes = config('order.webhook_events', [
            'order.created',
            'order.updated',
            'order.cancelled',
            'order.refunded',
            'order.fulfilled',
        ]);

        return view('order::admin.webhooks.edit', [
            'webhook' => $webhook,
            'channels' => $channels,
            'eventTypes' => $eventTypes,
        ]);
    }

    /**
     * Update the specified webhook in storage.
     *
     * @param  WebhookUpdateRequest  $request
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(WebhookUpdateRequest $request, int $id): RedirectResponse
    {
        if (! bouncer()->allows('orders.webhooks.edit')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        $this->webhookRepository->update($data, $id);

        return redirect()->route('admin.orders.webhooks.index')
            ->with('success', trans('order::app.admin.webhooks.update-success'));
    }

    /**
     * Remove the specified webhook from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        if (! bouncer()->allows('orders.webhooks.delete')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        try {
            $this->webhookRepository->delete($id);

            return response()->json([
                'message' => trans('order::app.admin.webhooks.delete-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.admin.webhooks.delete-failed'),
            ], 500);
        }
    }

    /**
     * Toggle webhook active status.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        if (! bouncer()->allows('orders.webhooks.edit')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        try {
            $webhook = $this->webhookRepository->findOrFail($id);

            $this->webhookRepository->update([
                'is_active' => ! $webhook->is_active,
            ], $id);

            return response()->json([
                'message' => trans('order::app.admin.webhooks.status-updated'),
                'is_active' => ! $webhook->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.admin.webhooks.status-update-failed'),
            ], 500);
        }
    }
}
