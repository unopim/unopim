<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Settings\DataTransfer\ExportDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DataTransfer\Contracts\Validator\JobInstances\JobValidator;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Jobs\Export\ExportTrackBatch;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Rules\SeparatorTypes;

class ExportController extends Controller
{
    const TYPE = 'export';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected Export $jobHelper
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ExportDataGrid::class)->toJson();
        }

        return view('admin::settings.data-transfer.exports.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $exporterConfig = config('exporters');

        return view('admin::settings.data-transfer.exports.create', compact('exporterConfig'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $exporterConfig = config('exporters');

        $exporters = array_keys($exporterConfig);

        $this->validate(request(), [
            'code'                => 'required|unique:job_instances,code',
            'entity_type'         => 'required|in:'.implode(',', $exporters),
            'filters'             => 'array',
            'field_separator'     => ['required_if:filters.file_format,Csv', new SeparatorTypes],
        ]);

        Event::dispatch('data_transfer.exports.create.before');

        $data = request()->only([
            'code',
            'entity_type',
            'field_separator',
            'filters',
        ]);

        Event::dispatch('data_transfer.exports.create.validate.before');

        $jobValidator = isset($exporterConfig[$data['entity_type']]['validator']) ? app($exporterConfig[$data['entity_type']]['validator']) : null;

        if ($jobValidator instanceof JobValidator) {
            $jobValidator->validate($data);
        }

        Event::dispatch('data_transfer.exports.create.validate.after');

        $export = $this->jobInstancesRepository->create(
            array_merge(
                [
                    'validation_strategy' => 'skip',
                    'type'                => self::TYPE,
                    'action'              => 'fetch',
                ],
                $data
            )
        );

        Event::dispatch('data_transfer.exports.create.after', $export);

        session()->flash('success', trans('admin::app.settings.data-transfer.exports.create-success'));

        return redirect()->route('admin.settings.data_transfer.exports.export-view', $export->id);
    }

    /**
     * Show the form for editing a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $exporterConfig = config('exporters');

        $export = $this->jobInstancesRepository->findOrFail($id);

        return view('admin::settings.data-transfer.exports.edit', compact('export', 'exporterConfig'));
    }

    /**
     * Update a resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $exporterConfig = config('exporters');

        $exporters = array_keys($exporterConfig);

        $export = $this->jobInstancesRepository->findOrFail($id);

        $this->validate(request(), [
            'code'                => 'required',
            'entity_type'         => 'required|in:'.implode(',', $exporters),
            'filters'             => 'array',
            'field_separator'     => ['required_if:filters.file_format,Csv', new SeparatorTypes],
        ]);

        Event::dispatch('data_transfer.exports.update.before');

        $data = array_merge(
            request()->only([
                'entity_type',
                'field_separator',
                'filters',
            ]),
            [
                'action'               => 'fetch',
                'validation_strategy'  => '',
                'validation_strategy'  => '',
                'allowed_errors'       => '',
                'state'                => 'pending',
                'processed_rows_count' => 0,
                'invalid_rows_count'   => 0,
                'errors_count'         => 0,
                'errors'               => null,
                'error_file_path'      => null,
                'started_at'           => null,
                'completed_at'         => null,
                'summary'              => null,
                'type'                 => self::TYPE,
            ]
        );

        Event::dispatch('data_transfer.exports.update.validate.before');

        $jobValidator = isset($exporterConfig[$data['entity_type']]['validator']) ? app($exporterConfig[$data['entity_type']]['validator']) : null;

        if ($jobValidator instanceof JobValidator) {
            $jobValidator->validate($data);
        }

        Event::dispatch('data_transfer.exports.update.validate.after');

        Storage::disk('private')->delete($export->error_file_path ?? '');

        $export = $this->jobInstancesRepository->update($data, $export->id);

        Event::dispatch('data_transfer.exports.update.after', $export);

        session()->flash('success', trans('admin::app.settings.data-transfer.exports.update-success'));

        return redirect()->route('admin.settings.data_transfer.exports.export-view', $export->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        try {
            if (! empty($export->file_path)) {
                Storage::disk('private')->delete($export->file_path);
            }

            if (! empty($export->error_file_path)) {
                Storage::disk('private')->delete($export->error_file_path);
            }

            $this->jobInstancesRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.delete-success'),
            ]);
        } catch (\Exception $e) {

            Log::error('Failed to delete job instance', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => trans('admin::app.settings.data-transfer.exports.delete-failed'),
        ], 500);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function exportView(int $id)
    {
        if (! bouncer()->hasPermission('data_transfer.export')) {
            abort(401, 'This action is unauthorized');
        }

        $export = $jobInstance = $this->jobInstancesRepository->findOrFail($id);

        $export->unsetRelations();

        return view('admin::settings.data-transfer.exports.export', compact('export'));
    }

    /**
     * exportNow function dispatch the job asynchronously
     */
    public function exportNow(int $id)
    {
        try {
            // Retrieve the export instance or fail with a 404
            $jobInstance = $this->jobInstancesRepository->findOrFail($id);

            // Get the authenticated user's ID
            $userId = auth()->guard('admin')->user()->id;

            // Dispatch an event before the export process starts
            Event::dispatch('data_transfer.exports.export.now.before', $jobInstance);

            $jobTrackInstance = $this->jobTrackRepository->create([
                'action'              => 'export',
                'validation_strategy' => 'skip',
                'type'                => 'export',
                'state'               => Export::STATE_PENDING,
                'allowed_errors'      => $jobInstance->allowed_errors,
                'field_separator'     => $jobInstance->field_separator,
                'file_path'           => $jobInstance->file_path,
                'meta'                => $jobInstance->toJson(),
                'job_instances_id'    => $jobInstance->id,
                'user_id'             => $userId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // Dispatch the Export job
            ExportTrackBatch::dispatch($jobTrackInstance);

            // Redirect to the tracker view
            return redirect()->route('admin.settings.data_transfer.tracker.view', $jobTrackInstance->id);
        } catch (\Exception $e) {
            // Log the error and redirect with an error message
            \Log::error('Import failed for job instance '.$id.': '.$e->getMessage());

            return redirect()->route('admin.settings.data_transfer.tracker.view', $id)
                ->with('error', 'Failed to start the export process. Please try again.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function validateExport(int $id): JsonResponse
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        $isValid = $this->jobHelper
            ->setExport($export)
            ->validate();

        return new JsonResponse([
            'is_valid' => $isValid,
            'export'   => $this->jobHelper->getExport()->unsetRelations(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function start(int $id): JsonResponse
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        if (! $export->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.nothing-to-export'),
            ], 400);
        }

        $this->jobHelper->setExport($export);

        if (! $this->jobHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.not-valid'),
            ], 400);
        }

        /**
         * Set the export state to processing
         */
        if ($export->state == Export::STATE_VALIDATED) {
            $this->jobHelper->started();
        }

        /**
         * Get the first pending batch to export
         */
        $exportBatch = $export->batches->where('state', Export::STATE_PENDING)->first();

        if ($exportBatch) {
            /**
             * Start the export process
             */
            try {
                $this->jobHelper->start();
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            if ($this->jobHelper->isLinkingRequired()) {
                $this->jobHelper->linking();
            } elseif ($this->jobHelper->isIndexingRequired()) {
                $this->jobHelper->indexing();
            } else {
                $this->jobHelper->completed();
            }
        }

        return new JsonResponse([
            'stats'  => $this->jobHelper->stats(Export::STATE_PROCESSED),
            'export' => $this->jobHelper->getExport()->unsetRelations(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function link(int $id): JsonResponse
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        if (! $export->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.nothing-to-export'),
            ], 400);
        }

        $this->jobHelper->setExport($export);

        if (! $this->jobHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.not-valid'),
            ], 400);
        }

        /**
         * Set the export state to linking
         */
        if ($export->state == Export::STATE_PROCESSED) {
            $this->jobHelper->linking();
        }

        /**
         * Get the first processing batch to link
         */
        $exportBatch = $export->batches->where('state', Export::STATE_PROCESSED)->first();

        /**
         * Set the export state to linking/completed
         */
        if ($exportBatch) {
            /**
             * Start the resource linking process
             */
            try {
                $this->jobHelper->link($exportBatch);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            if ($this->jobHelper->isIndexingRequired()) {
                $this->jobHelper->indexing();
            } else {
                $this->jobHelper->completed();
            }
        }

        return new JsonResponse([
            'stats'  => $this->jobHelper->stats(Export::STATE_LINKED),
            'export' => $this->jobHelper->getExport()->unsetRelations(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function indexData(int $id): JsonResponse
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        if (! $export->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.nothing-to-export'),
            ], 400);
        }

        $this->jobHelper->setExport($export);

        if (! $this->jobHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.exports.not-valid'),
            ], 400);
        }

        /**
         * Set the export state to linking
         */
        if ($export->state == Export::STATE_LINKED) {
            $this->jobHelper->indexing();
        }

        /**
         * Get the first processing batch to link
         */
        $exportBatch = $export->batches->where('state', Export::STATE_LINKED)->first();

        /**
         * Set the export state to linking/completed
         */
        if ($exportBatch) {
            /**
             * Start the resource linking process
             */
            try {
                $this->jobHelper->index($exportBatch);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            /**
             * Set the export state to completed
             */
            $this->jobHelper->completed();
        }

        return new JsonResponse([
            'stats'  => $this->jobHelper->stats(Export::STATE_INDEXED),
            'export' => $this->jobHelper->getImport()->unsetRelations(),
        ]);
    }

    /**
     * Returns export stats
     */
    public function stats(int $id, string $state = Export::STATE_PROCESSED): JsonResponse
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        $stats = $this->jobHelper
            ->setExport($export)
            ->stats($state);

        return new JsonResponse([
            'stats'  => $stats,
            'export' => $this->jobHelper->getExport()->unsetRelations(),
        ]);
    }

    /**
     * Download export error report
     */
    public function downloadSample(string $type)
    {
        $exporter = config('exporters.'.$type);

        return Storage::download($exporter['sample_path']);
    }

    /**
     * Download export error report
     */
    public function download(int $id)
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId) && ($export->tenant_id ?? null) !== $tenantId) {
            abort(403, 'Access denied.');
        }

        return Storage::disk('private')->download($export->file_path);
    }

    /**
     * Download export error report
     */
    public function downloadErrorReport(int $id)
    {
        $export = $this->jobInstancesRepository->findOrFail($id);

        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId) && ($export->tenant_id ?? null) !== $tenantId) {
            abort(403, 'Access denied.');
        }

        return Storage::disk('private')->download($export->error_file_path);
    }
}
