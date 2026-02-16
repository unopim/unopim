<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\DataGrids\SyncJobDataGrid;
use Webkul\ChannelConnector\Repositories\ChannelSyncJobRepository;
use Webkul\ChannelConnector\Services\SyncJobManager;

class SyncDashboardController extends Controller
{
    public function __construct(
        protected ChannelSyncJobRepository $syncJobRepository,
        protected SyncJobManager $syncJobManager,
    ) {}

    /**
     * Display sync dashboard with all jobs across all connectors.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('channel_connector.sync.view')) {
            abort(401, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            return app(SyncJobDataGrid::class)->toJson();
        }

        return view('channel_connector::admin.dashboard.index');
    }

    /**
     * Show detailed view of a single sync job.
     */
    public function show(int $id)
    {
        if (! bouncer()->hasPermission('channel_connector.sync.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $job = $this->syncJobRepository->find($id);

        if (! $job) {
            abort(404);
        }

        $job->load('connector');

        return view('channel_connector::admin.dashboard.show', compact('job'));
    }

    /**
     * Return JSON status of a sync job (for AJAX polling from admin UI).
     */
    public function status(int $id)
    {
        if (! bouncer()->hasPermission('channel_connector.sync.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $job = $this->syncJobRepository->find($id);

        if (! $job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        return response()->json([
            'id'              => $job->id,
            'status'          => $job->status,
            'total_products'  => $job->total_products ?? 0,
            'synced_products' => $job->synced_products ?? 0,
            'failed_products' => $job->failed_products ?? 0,
        ]);
    }

    /**
     * Retry a failed sync job.
     */
    public function retry(int $id)
    {
        if (! bouncer()->hasPermission('channel_connector.sync.create')) {
            abort(401, 'This action is unauthorized.');
        }

        $job = $this->syncJobRepository->find($id);

        if (! $job) {
            abort(404);
        }

        if ($job->status !== 'failed') {
            return redirect()->back()->withErrors(['retry' => trans('channel_connector::app.dashboard.retry-only-failed')]);
        }

        $this->syncJobManager->retryFailedProducts($job);

        session()->flash('success', trans('channel_connector::app.sync.retry-success'));

        return redirect()->route('admin.channel_connector.dashboard.show', $id);
    }
}
