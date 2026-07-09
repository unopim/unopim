@props([
    'title',
    'info' => '',
    'key'  => null,
])

<div
    data-settings-section
    {{ $attributes->merge(['class' => 'bg-white dark:bg-cherry-900 rounded box-shadow overflow-hidden']) }}
>
    <div class="p-4 border-b border-gray-200 dark:border-cherry-800">
        <p class="text-base font-semibold text-gray-800 dark:text-slate-50">{{ $title }}</p>

        @if ($info)
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">{{ $info }}</p>
        @endif
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
