@php
    $channels = core()->getAllChannels();

    $currentChannel = core()->getRequestedChannel();

    $currentLocale = core()->getRequestedLocale();
@endphp

<x-admin::page
    :title="trans($entry['name'])"
    :subtitle="isset($entry['info']) ? trans($entry['info']) : ''"
    :back="route('admin.settings.system.index')"
    :action="route('admin.settings.system.update', $entry['key'])"
    method="PUT"
    :ajax="true"
    enctype="multipart/form-data"
>
    <x-slot:actions>
        <button type="submit" class="primary-button">
            @lang('admin::app.settings.system-settings.save-btn')
        </button>
    </x-slot>

    {!! view_render_event('unopim.admin.system_settings.edit.'.$entry['key'].'.before', ['entry' => $entry]) !!}

    <div class="mt-6 grid gap-1.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        {{-- $group is the effective field group: the entry itself for inline `fields`,
             or the referenced config('core') group — its `key` drives the config codes. --}}
        @php ($item = $group)

        @foreach ($group['fields'] as $field)
            @if ($field['type'] == 'blade' && view()->exists($path = $field['path']))
                {!! view($path, compact('field', 'item'))->render() !!}
            @else
                @include('admin::configuration.field-type')
            @endif
        @endforeach
    </div>

    {!! view_render_event('unopim.admin.system_settings.edit.'.$entry['key'].'.after', ['entry' => $entry]) !!}
</x-admin::page>
