<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.tracker.index.title')
    </x-slot>

    <x-admin::data-transfer.job-tracker
        :job-track="$import"
        :job-instance="$jobInstance"
        :job-type="$jobType"
        :stats="$stats"
        :summary="$summary"
        :is-valid="$isValid"
        :messages="$messages"
    />
</x-admin::layouts>
