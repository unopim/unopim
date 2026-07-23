<?php

namespace Webkul\Webhook\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Webhook\DataGrids\LogsDataGrid;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Repositories\LogsRepository;

class WebhookLogsController
{
    public function __construct(
        protected LogsRepository $logsRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.logs'), 403, trans('webhook::app.configuration.webhook.logs.index.unauthorized'));

        if (request()->ajax()) {
            return resolve(LogsDataGrid::class)->toJson();
        }

        return view('webhook::logs.index');
    }

    /**
     * Delivery log feed for a single webhook. The id travels in the path so the
     * scope survives whatever query string the datagrid rebuilds.
     */
    public function forWebhook(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.logs'), 403, trans('webhook::app.configuration.webhook.logs.index.unauthorized'));

        return resolve(LogsDataGrid::class)->forWebhook($id)->toJson();
    }

    /**
     * Return the specified log entry as JSON for the view modal.
     */
    public function show(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.logs.view'), 403, trans('webhook::app.configuration.webhook.logs.index.unauthorized'));

        $log = $this->logsRepository->findOrFail($id);

        $webhookName = $log->webhook_id
            ? Webhook::query()->whereKey($log->webhook_id)->value('name')
            : null;

        return new JsonResponse([
            'id'         => $log->id,
            'webhook'    => $webhookName,
            'sku'        => $log->sku,
            'event'      => $log->event,
            'user'       => $log->user,
            'status'     => (bool) $log->status,
            'http_code'  => $log->http_code,
            'created_at' => $log->created_at?->toDateTimeString(),
            'payload'    => ($log->extra ?? [])['payload'] ?? null,
            'response'   => ($log->extra ?? [])['response'] ?? null,
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.logs.delete'), 403, trans('webhook::app.configuration.webhook.logs.index.unauthorized'));

        try {
            $this->logsRepository->delete($id);

            return new JsonResponse([
                'message' => trans('webhook::app.configuration.webhook.logs.index.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'message' => trans('webhook::app.configuration.webhook.logs.index.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete locales from the locale datagrid
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.logs.mass_delete'), 403, trans('webhook::app.configuration.webhook.logs.index.unauthorized'));

        $logIds = $massDestroyRequest->input('indices');

        foreach ($logIds as $logId) {
            $log = $this->logsRepository->find($logId);

            if (! $log) {
                continue;
            }

            try {
                $this->logsRepository->delete($logId);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('webhook::app.configuration.webhook.logs.index.delete-success'),
        ], JsonResponse::HTTP_OK);
    }
}
