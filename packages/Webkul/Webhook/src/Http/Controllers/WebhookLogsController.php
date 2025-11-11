<?php

namespace Webkul\Webhook\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Webhook\DataGrids\LogsDataGrid;
use Webkul\Webhook\Repositories\LogsRepository;

class WebhookLogsController
{
    public function __construct(
        protected LogsRepository $logsRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(LogsDataGrid::class)->toJson();
        }

        return view('webhook::logs.index');
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(int $id): JsonResponse
    {
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
