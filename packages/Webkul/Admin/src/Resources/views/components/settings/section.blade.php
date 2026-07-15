@props([
    'title',
    'info' => '',
    'key'  => null,
])

<div
    data-settings-section
    {{ $attributes->merge(['class' => 'bg-white dark:bg-cherry-800 rounded-2xl border border-gray-100 dark:border-cherry-700 overflow-hidden']) }}
>
    <div class="p-4 border-b border-gray-200 dark:border-cherry-700">
        <x-admin::heading :title="$title" :subtitle="$info" as="h3" size="md" />
    </div>

    {{-- Extension point: plugins inject rows into this section with zero core edits. --}}
    @if ($key)
        {!! view_render_event('unopim.admin.system_settings.section.'.$key.'.before') !!}
    @endif

    {{ $slot }}

    @if ($key)
        {!! view_render_event('unopim.admin.system_settings.section.'.$key.'.after') !!}
    @endif
</div>
