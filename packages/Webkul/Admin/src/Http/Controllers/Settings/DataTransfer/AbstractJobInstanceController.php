<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Settings\DataTransfer\ImportDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\Tenant\Filesystem\TenantStorage;

abstract class AbstractJobInstanceController extends Controller
{
    const TYPE = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected Import $importHelper
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
            'field_separator'     => 'required',
            'file'                => 'required|mimes:csv,xls,xlsx,txt',
        ]);

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
                        TenantStorage::path('imports'),
                        time().'-'.request()->file('file')->getClientOriginalName(),
                        'private'
                    ),
                    'type'  => self::TYPE,
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
            'field_separator'     => 'required',
            'file'                => 'mimes:csv,xls,xlsx,txt',
        ]);

        Event::dispatch('data_transfer.imports.update.before');

        $data = array_merge(
            request()->only([
                'code',
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
    public function import(int $id)
    {
        $import = $this->jobInstancesRepository->findOrFail($id);

        // $isValid = $this->importHelper
        //     ->setImport($import)
        //     ->isValid();

        // if ($import->state == Import::STATE_LINKING) {
        //     if ($this->importHelper->isIndexingRequired()) {
        //         $state = Import::STATE_INDEXING;
        //     } else {
        //         $state = Import::STATE_COMPLETED;
        //     }
        // } elseif ($import->state == Import::STATE_INDEXING) {
        //     $state = Import::STATE_COMPLETED;
        // } else {
        //     $state = Import::STATE_COMPLETED;
        // }

        // $stats = $this->importHelper->stats($state);

        $import->unsetRelations();

        return view('admin::settings.data-transfer.imports.import', compact('import'));
    }

    /**
     * importNow function dispatch the job asynchronously
     */
    abstract public function importNow(int $id);

    /**
     * Store a newly created resource in storage.
     */
    public function validateImport(int $id): JsonResponse
    {
        $import = $this->jobInstancesRepository->findOrFail($id);

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
        $import = $this->jobInstancesRepository->findOrFail($id);

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
        $import = $this->jobInstancesRepository->findOrFail($id);

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
        $import = $this->jobInstancesRepository->findOrFail($id);

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
    public function stats(int $id, string $state): JsonResponse
    {
        $import = $this->jobTrackRepository->findOrFail($id);

        $stats = $this->importHelper
            ->setImport($import)
            ->stats($state);

        return new JsonResponse([
            'stats'  => $stats,
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
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
        $import = $this->jobInstancesRepository->findOrFail($id);

        return Storage::disk('private')->download($import->error_file_path);
    }
}
