<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\DataTransfer\JobTrackerGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;
use ZipArchive;

class TrackerController extends Controller
{
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
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(JobTrackerGrid::class)->toJson();
        }

        return view('admin::settings.data-transfer.tracker.index');
    }

    /**
     * Display a listing of the resource.
     */
    public function view($batchId = null): View
    {
        if (! bouncer()->hasPermission('data_transfer.job_tracker')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $import = $this->jobTrackRepository->findOrFail($batchId);
        $jobInstance = json_decode($import->meta, true);
        $summary = $this->normalizeSummary($import->summary);

        $batchState = $this->mapJobStateToBatchState($import->state);

        if ($jobInstance['type'] == 'export') {
            $isValid = $this->exportHelper->setExport($import)->isValid();
            $stats = $this->exportHelper->stats($batchState);
        } else {
            $isValid = $this->importHelper->setImport($import)->isValid();
            $stats = $this->importHelper->stats($batchState);
        }

        return view('admin::settings.data-transfer.tracker.import', compact(
            'import',
            'isValid',
            'stats',
            'jobInstance',
            'summary',
        ));
    }

    /**
     * Map job track state to the corresponding batch state for stats queries.
     */
    private function mapJobStateToBatchState(string $jobState): string
    {
        return match ($jobState) {
            'processing', 'processed'  => 'processed',
            'linking', 'linked'        => 'linked',
            'indexing', 'indexed'      => 'indexed',
            'completed'                => 'processed',
            default                    => $jobState,
        };
    }

    /**
     * Normalizes the summary data by translating keys and handling null values.
     *
     * @param  array|null  $summary  The summary data to be normalized.
     * @return array The normalized summary data.
     */
    private function normalizeSummary($summary)
    {
        $summaryData = [];

        foreach (($summary ?? []) as $key => $value) {
            $summaryData[trans(sprintf('admin::app.settings.data-transfer.tracker.summary.%s', $key))] = $value ?? 0;
        }

        // Return the normalized summary data
        return $summaryData;
    }

    /**
     * Download
     */
    public function download(int $id)
    {
        if (! bouncer()->hasPermission('data_transfer.job_tracker')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $import = $this->jobTrackRepository->findOrFail($id);

        return Storage::disk('public')->download($import->file_path);
    }

    /**
     * Download archive
     */
    public function downloadArchive(int $id)
    {
        if (! bouncer()->hasPermission('data_transfer.job_tracker')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $jobTrack = $this->jobTrackRepository->findOrFail($id);
        $zip = new ZipArchive;

        // Slug the parts and basename the result so a job code containing "../"
        // can never escape the temp directory (arbitrary file write). The archive
        // is built in a private temp path, not the web-served public/ directory.
        $zipFileName = basename(sprintf(
            '%s-%s.zip',
            Str::slug((string) $jobTrack->jobInstance->code),
            Str::slug((string) $jobTrack->jobInstance->entity_type)
        ));

        $zipFilePath = storage_path('app/tmp/'.$zipFileName);

        if (! is_dir(dirname($zipFilePath))) {
            mkdir(dirname($zipFilePath), 0755, true);
        }

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
            $folderPath = $jobTrack->file_path;
            $files = Storage::allFiles($folderPath);
            $directories = Storage::allDirectories($folderPath);

            // Add files to the ZIP archive
            foreach ($files as $file) {
                $relativePath = str_replace($folderPath.'/', '', $file);
                $zip->addFile(Storage::path($file), $relativePath);
            }

            // Add directories to the ZIP archive
            foreach ($directories as $directory) {
                $relativePath = str_replace($folderPath.'/', '', $directory);
                $zip->addEmptyDir($relativePath);
            }

            $zip->close();

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }

        return response()->json([
            'message' => trans('admin::app.settings.data-transfer.tracker.zip-failed'),
        ], 500);
    }

    /**
     * Download Log file for the job
     */
    public function downloadLogFile(int $id)
    {
        if (! bouncer()->hasPermission('data_transfer.job_tracker')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $path = JobLogger::getJobLogPath($id);

        $path = storage_path($path);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }
}
