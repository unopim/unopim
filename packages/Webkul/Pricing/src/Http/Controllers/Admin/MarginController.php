<?php

namespace Webkul\Pricing\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Pricing\DataGrids\MarginEventDataGrid;
use Webkul\Pricing\Events\MarginApproved;
use Webkul\Pricing\Events\MarginRejected;
use Webkul\Pricing\Repositories\MarginProtectionEventRepository;

class MarginController extends Controller
{
    public function __construct(
        protected MarginProtectionEventRepository $marginEventRepository,
    ) {}

    /**
     * Display a listing of pending/recent margin protection events.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('pricing.margins.view')) {
            abort(403, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            return app(MarginEventDataGrid::class)->toJson();
        }

        return view('pricing::admin.margins.index');
    }

    /**
     * Display detail view of a margin protection event.
     */
    public function show(int $id)
    {
        if (! bouncer()->hasPermission('pricing.margins.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $marginEvent = $this->marginEventRepository->find($id);

        if (! $marginEvent) {
            abort(404);
        }

        $marginEvent->load(['product', 'channel', 'approver']);

        return view('pricing::admin.margins.show', compact('marginEvent'));
    }

    /**
     * Approve a margin protection event with a reason.
     */
    public function approve(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('pricing.margins.approve')) {
            abort(403, 'This action is unauthorized.');
        }

        $marginEvent = $this->marginEventRepository->find($id);

        if (! $marginEvent) {
            abort(404);
        }

        if (! $marginEvent->isPending()) {
            session()->flash('warning', trans('pricing::app.margins.not-pending'));

            return redirect()->route('admin.pricing.margins.show', $id);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $adminId = auth()->guard('admin')->id();

        $this->marginEventRepository->update([
            'event_type'  => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'reason'      => $request->input('reason'),
        ], $id);

        $marginEvent = $marginEvent->fresh();

        event(new MarginApproved($marginEvent, $adminId));

        session()->flash('success', trans('pricing::app.margins.approve-success'));

        return redirect()->route('admin.pricing.margins.show', $id);
    }

    /**
     * Reject a margin protection event with a reason.
     */
    public function reject(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('pricing.margins.reject')) {
            abort(403, 'This action is unauthorized.');
        }

        $marginEvent = $this->marginEventRepository->find($id);

        if (! $marginEvent) {
            abort(404);
        }

        if (! $marginEvent->isPending()) {
            session()->flash('warning', trans('pricing::app.margins.not-pending'));

            return redirect()->route('admin.pricing.margins.show', $id);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $adminId = auth()->guard('admin')->id();

        $this->marginEventRepository->update([
            'event_type' => 'rejected',
            'reason'     => $request->input('reason'),
        ], $id);

        $marginEvent = $marginEvent->fresh();

        event(new MarginRejected($marginEvent, $adminId, $request->input('reason')));

        session()->flash('success', trans('pricing::app.margins.reject-success'));

        return redirect()->route('admin.pricing.margins.show', $id);
    }
}
