@props([
    'jobTrack',
    'jobInstance',
    'jobType',
    'stats',
    'summary',
    'isValid',
    'messages',
])

@php
    $editRoute = $jobType->editRouteName();

    $endpoints = [
        'pause'  => route('admin.settings.data_transfer.jobs.pause', $jobTrack->id),
        'resume' => route('admin.settings.data_transfer.jobs.resume', $jobTrack->id),
        'cancel' => route('admin.settings.data_transfer.jobs.cancel', $jobTrack->id),
        'stats'  => route('admin.settings.data_transfer.jobs.stats', $jobTrack->id),
    ];
@endphp

{!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.before', $jobTrack) !!}

@include('admin::components.data-transfer.job-tracker.header')

<v-job-tracker
    :initial-job-track='@json($jobTrack)'
    :initial-job-instance='@json($jobInstance)'
    :initial-stats='@json($stats)'
    :initial-summary='@json($summary)'
    :initial-is-valid='@json($isValid)'
    :endpoints='@json($endpoints)'
    :messages='@json($messages)'
/>

{!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.after', $jobTrack) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-job-tracker-template"
    >
        <div class="mt-3.5 rounded-lg border border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900 overflow-hidden box-shadow">

            @include('admin::components.data-transfer.job-tracker.states.pending')

            @include('admin::components.data-transfer.job-tracker.states.validating')

            @include('admin::components.data-transfer.job-tracker.states.validated')

            @include('admin::components.data-transfer.job-tracker.states.failed')

            @include('admin::components.data-transfer.job-tracker.states.paused')

            @include('admin::components.data-transfer.job-tracker.states.cancelled')

            @include('admin::components.data-transfer.job-tracker.states.processing')

            @include('admin::components.data-transfer.job-tracker.states.linking')

            @include('admin::components.data-transfer.job-tracker.states.indexing')

            @include('admin::components.data-transfer.job-tracker.states.completed')

        </div>
    </script>

    @include('admin::components.data-transfer.job-tracker.script')
@endPushOnce
