<div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
        <a
            href="{{ route('admin.settings.data_transfer.imports.index') }}"
            class="text-gray-600 hover:bg-primary-100 dark:hover:bg-gray-800 dark:text-white"
        >
            @lang('admin::app.settings.data-transfer.tracker.index.title')
        </a>

        - {{ ucfirst(trans($jobTrack->jobInstance->entity_type)) }} / {{ ucfirst(trans($jobTrack->jobInstance->code)) }}
    </p>

    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.header.actions.before', $jobTrack) !!}

    @if ($editRoute)
        <a
            href="{{ route($editRoute, $jobTrack->job_instances_id) }}"
            class="primary-button"
        >
            @lang('admin::app.settings.data-transfer.tracker.import.edit-btn')
        </a>
    @endif

    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.header.actions.after', $jobTrack) !!}
</div>
