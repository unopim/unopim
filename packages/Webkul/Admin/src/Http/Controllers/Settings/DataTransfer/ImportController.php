<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Settings\DataTransfer\ImportDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Rules\SeparatorTypes;

class ImportController extends Controller
{
    const TYPE = 'import';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected Import $importHelper,
        protected Export $exportHelper
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ImportDataGrid::class)->toJson();
        }

        return view('admin::settings.data-transfer.imports.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::settings.data-transfer.imports.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $importers = array_keys(config('importers'));

        $this->validate(request(), [
            'code'                => 'required|unique:job_instances,code',
            'entity_type'         => 'required|in:'.implode(',', $importers),
            'action'              => 'required:in:append,delete',
            'validation_strategy' => 'required:in:stop-on-errors,skip-errors',
            'allowed_errors'      => 'required|integer|min:0',
            'field_separator'     => ['required', new SeparatorTypes()],
            'file'                => 'required|mimes:csv,xls,xlsx,txt',
        ], ['file.mimes' => trans('core::validation.file-type')]);

        Event::dispatch('data_transfer.imports.create.before');

        $data = request()->only([
            'code',
            'entity_type',
            'action',
            'validation_strategy',
            'validation_strategy',
            'allowed_errors',
            'field_separator',
            'images_directory_path',
        ]);

        $import = $this->jobInstancesRepository->create(
            array_merge(
                [
                    'file_path' => request()->file('file')->storeAs(
                        'imports',
                        time().'-'.request()->file('file')->getClientOriginalName(),
                        'private'
                    ),
                    'type' => self::TYPE,
                ],
                $data
            )
        );

        Event::dispatch('data_transfer.imports.create.after', $import);

        session()->flash('success', trans('admin::app.settings.data-transfer.imports.create-success'));

        return redirect()->route('admin.settings.data_transfer.imports.import-view', $import->id);
    }

    /**
     * Show the form for editing a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $import = $this->jobInstancesRepository->findOrFail($id);

        return view('admin::settings.data-transfer.imports.edit', compact('import'));
    }

    /**
     * Update a resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $importers = array_keys(config('importers'));

        $import = $this->jobInstancesRepository->findOrFail($id);

        $this->validate(request(), [
            'code'                => 'required',
            'entity_type'         => 'required|in:'.implode(',', $importers),
            'action'              => 'required:in:append,delete',
            'validation_strategy' => 'required:in:stop-on-errors,skip-errors',
            'allowed_errors'      => 'required|integer|min:0',
            'field_separator'     => ['required', new SeparatorTypes()],
            'file'                => 'mimes:csv,xls,xlsx,txt',
        ], ['file.mimes' => trans('core::validation.file-type')]);

        Event::dispatch('data_transfer.imports.update.before');

        $data = array_merge(
            request()->only([
                'entity_type',
                'action',
                'validation_strategy',
                'validation_strategy',
                'allowed_errors',
                'field_separator',
                'images_directory_path',
            ]),
            [
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

        Storage::disk('private')->delete($import->error_file_path ?? '');

        if (request()->file('file') && request()->file('file')->isValid()) {
            Storage::disk('private')->delete($import->file_path);

            $data['file_path'] = request()->file('file')->storeAs(
                'imports',
                time().'-'.request()->file('file')->getClientOriginalName(),
                'private'
            );
        }

        $import = $this->jobInstancesRepository->update($data, $import->id);

        Event::dispatch('data_transfer.imports.update.after', $import);

        session()->flash('success', trans('admin::app.settings.data-transfer.imports.update-success'));

        return redirect()->route('admin.settings.data_transfer.imports.import-view', $import->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $import = $this->jobInstancesRepository->findOrFail($id);

        try {
            Storage::disk('private')->delete($import->file_path);

            Storage::disk('private')->delete($import->error_file_path ?? '');

            $this->jobInstancesRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return response()->json([
            'message' => trans('admin::app.settings.data-transfer.imports.delete-failed'),
        ], 500);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function importView(int $id)
    {
        if (! bouncer()->hasPermission('data_transfer.imports')) {
            abort(401, 'This action is unauthorized');
        }

        $import = $jobInstance = $this->jobInstancesRepository->findOrFail($id);

        $import->unsetRelations();

        return view('admin::settings.data-transfer.imports.import', compact('import'));
    }

    /**
     * importNow function dispatch the job asynchronously
     */
    public function importNow(int $id)
    {
        try {
            // Retrieve the import instance or fail with a 404
            $import = $this->jobInstancesRepository->findOrFail($id);

            // Get the authenticated user's ID
            $userId = auth()->guard('admin')->user()->id;

            // Dispatch an event before the import process starts
            Event::dispatch('data_transfer.imports.import.now.before', $import);

            // Create a job track instance
            $jobTrackInstance = $this->jobTrackRepository->create([
                'state'                 => Import::STATE_PENDING,
                'validation_strategy'   => $import->validation_strategy,
                'allowed_errors'        => $import->allowed_errors,
                'field_separator'       => $import->field_separator,
                'file_path'             => $import->file_path,
                'images_directory_path' => $import->images_directory_path,
                'meta'                  => $import->toJson(),
                'job_instances_id'      => $import->id,
                'user_id'               => $userId,
                'created_at'            => now(),
                'updated_at'            => now(),
                'action'                => $import->action,
            ]);

            // Dispatch the import job
            ImportTrackBatch::dispatch($jobTrackInstance);

            // Redirect to the tracker view
            return redirect()->route('admin.settings.data_transfer.tracker.view', $jobTrackInstance->id);
        } catch (\Exception $e) {
            // Log the error and redirect with an error message
            \Log::error('Import failed for job instance '.$id.': '.$e->getMessage());

            return redirect()->route('admin.settings.data_transfer.tracker.view', ['id' => $id])
                ->with('error', 'Failed to start the import process. Please try again.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function validateImport(int $id): JsonResponse
    {
        $import = $this->jobTrackRepository->findOrFail($id);

        $isValid = $this->importHelper
            ->setImport($import)
            ->validate();

        return new JsonResponse([
            'is_valid' => $isValid,
            'import'   => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function start(int $id): JsonResponse
    {
        $import = $this->jobTrackRepository->findOrFail($id);

        if (! $import->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import'),
            ], 400);
        }

        $this->importHelper->setImport($import);

        if (! $this->importHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.not-valid'),
            ], 400);
        }

        /**
         * Set the import state to processing
         */
        if ($import->state == Import::STATE_VALIDATED) {
            $this->importHelper->started();
        }

        /**
         * Get the first pending batch to import
         */
        $importBatch = $import->batches->where('state', Import::STATE_PENDING)->first();

        if ($importBatch) {
            /**
             * Start the import process
             */
            try {
                $this->importHelper->start();
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            if ($this->importHelper->isLinkingRequired()) {
                $this->importHelper->linking();
            } elseif ($this->importHelper->isIndexingRequired()) {
                $this->importHelper->indexing();
            } else {
                $this->importHelper->completed();
            }
        }

        return new JsonResponse([
            'stats'  => $this->importHelper->stats(Import::STATE_PROCESSED),
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function link(int $id): JsonResponse
    {
        $import = $this->jobTrackRepository->findOrFail($id);

        if (! $import->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import'),
            ], 400);
        }

        $this->importHelper->setImport($import);

        if (! $this->importHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.not-valid'),
            ], 400);
        }

        /**
         * Set the import state to linking
         */
        if ($import->state == Import::STATE_PROCESSED) {
            $this->importHelper->linking();
        }

        /**
         * Get the first processing batch to link
         */
        $importBatch = $import->batches->where('state', Import::STATE_PROCESSED)->first();

        /**
         * Set the import state to linking/completed
         */
        if ($importBatch) {
            /**
             * Start the resource linking process
             */
            try {
                $this->importHelper->link($importBatch);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            if ($this->importHelper->isIndexingRequired()) {
                $this->importHelper->indexing();
            } else {
                $this->importHelper->completed();
            }
        }

        return new JsonResponse([
            'stats'  => $this->importHelper->stats(Import::STATE_LINKED),
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function indexData(int $id): JsonResponse
    {
        $import = $this->jobTrackRepository->findOrFail($id);

        if (! $import->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import'),
            ], 400);
        }

        $this->importHelper->setImport($import);

        if (! $this->importHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.not-valid'),
            ], 400);
        }

        /**
         * Set the import state to linking
         */
        if ($import->state == Import::STATE_LINKED) {
            $this->importHelper->indexing();
        }

        /**
         * Get the first processing batch to link
         */
        $importBatch = $import->batches->where('state', Import::STATE_LINKED)->first();

        /**
         * Set the import state to linking/completed
         */
        if ($importBatch) {
            /**
             * Start the resource linking process
             */
            try {
                $this->importHelper->index($importBatch);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            /**
             * Set the import state to completed
             */
            $this->importHelper->completed();
        }

        return new JsonResponse([
            'stats'  => $this->importHelper->stats(Import::STATE_INDEXED),
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    /**
     * Returns import stats
     */
    public function stats(int $id, $state = Import::STATE_PROCESSED): JsonResponse
    {
        $import = $this->jobTrackRepository->findOrFail($id);
        $jobInstance = json_decode($import->meta, true);
        $summary = $this->normalizeSummary($import->summary);

        if ($jobInstance['type'] == 'export') {
            $isValid = $this->exportHelper->setExport($import)->isValid();
            $stats = $this->exportHelper->stats($state);
            $jobTrack = $this->exportHelper->getExport()->unsetRelations();
        } else {
            $isValid = $this->importHelper->setImport($import)->isValid();
            $stats = $this->importHelper->stats($state);
            $jobTrack = $this->importHelper->getImport()->unsetRelations();
        }

        $stats['summary'] = $this->normalizeSummary($stats['summary']);

        return new JsonResponse([
            'isValid'     => $isValid,
            'stats'       => $stats,
            'import'      => $jobTrack,
            'jobInstance' => $jobInstance,
            'summary'     => $summary,
        ]);
    }

    /**
     * Normalizes the summary data by translating keys and handling null values.
     *
     * @param  array|null  $summary  The summary data to be normalized.
     * @return array The normalized summary data.
     */
    private function normalizeSummary($summery)
    {
        $summaryData = [];

        // Loop through the summary data, translating keys and handling null values
        foreach (($summery ?? []) as $key => $value) {
            $summaryData[trans(sprintf('admin::app.settings.data-transfer.tracker.summary.%s', $key))] = $value ?? 0;
        }

        // Return the normalized summary data
        return $summaryData;
    }

    /**
     * Download import error report
     */
    public function downloadSample(string $type)
    {
        $importer = config('importers.'.$type);

        return Storage::download($importer['sample_path']);
    }

    /**
     * Download import error report
     */
    public function download(int $id)
    {
        $import = $this->jobInstancesRepository->findOrFail($id);

        return Storage::disk('private')->download($import->file_path);
    }

    /**
     * Download import error report
     */
    public function downloadErrorReport(int $id)
    {
        $import = $this->jobTrackRepository->findOrFail($id);

        return Storage::disk('private')->download($import->error_file_path);
    }
}
