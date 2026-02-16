<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\ChannelConnector\DataGrids\ConflictDataGrid;
use Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository;
use Webkul\ChannelConnector\Services\ConflictResolver;

class ConflictController extends Controller
{
    public function __construct(
        protected ChannelSyncConflictRepository $conflictRepository,
        protected ConflictResolver $conflictResolver,
    ) {}

    /**
     * Display a listing of sync conflicts.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('channel_connector.conflicts.view')) {
            abort(401, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            return app(ConflictDataGrid::class)->toJson();
        }

        return view('channel_connector::admin.conflicts.index');
    }

    /**
     * Display conflict detail with per-locale field diff.
     */
    public function show(int $id)
    {
        if (! bouncer()->hasPermission('channel_connector.conflicts.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $conflict = $this->conflictRepository->find($id);

        if (! $conflict) {
            abort(404);
        }

        $conflict->load(['connector', 'product', 'syncJob', 'resolvedBy']);

        $conflictingFields = $conflict->conflicting_fields ?? [];

        // Gather all locales from conflicting fields for tab rendering
        $locales = [];

        foreach ($conflictingFields as $fieldCode => $fieldData) {
            if (! empty($fieldData['locales'])) {
                foreach (array_keys($fieldData['locales']) as $locale) {
                    if (! in_array($locale, $locales)) {
                        $locales[] = $locale;
                    }
                }
            }
        }

        return view('channel_connector::admin.conflicts.show', compact('conflict', 'conflictingFields', 'locales'));
    }

    /**
     * Resolve a sync conflict.
     */
    public function resolve(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('channel_connector.conflicts.edit')) {
            abort(401, 'This action is unauthorized.');
        }

        $conflict = $this->conflictRepository->find($id);

        if (! $conflict) {
            abort(404);
        }

        if ($conflict->resolution_status !== 'unresolved') {
            session()->flash('warning', trans('channel_connector::app.conflicts.already-resolved', [
                'status' => $conflict->resolution_status,
            ]));

            return redirect()->route('admin.channel_connector.conflicts.show', $id);
        }

        $request->validate([
            'resolution'        => ['required', Rule::in(['pim_wins', 'channel_wins', 'merged', 'dismissed'])],
            'field_overrides'   => ['nullable', 'array'],
            'field_overrides.*' => [Rule::in(['pim', 'channel'])],
        ]);

        try {
            $this->conflictResolver->resolveConflict(
                $conflict,
                $request->input('resolution'),
                $request->input('field_overrides'),
            );

            session()->flash('success', trans('channel_connector::app.conflicts.resolve-success'));
        } catch (\Exception $e) {
            session()->flash('error', trans('channel_connector::app.conflicts.resolve-failed'));
        }

        return redirect()->route('admin.channel_connector.conflicts.show', $id);
    }
}
