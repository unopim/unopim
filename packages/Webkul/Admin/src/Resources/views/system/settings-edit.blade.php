@php
    $channels = core()->getAllChannels();

    $currentChannel = core()->getRequestedChannel();

    $currentLocale = core()->getRequestedLocale();
@endphp

<x-admin::layouts>
    <x-slot:title>@lang($entry['name'])</x-slot>

    {!! view_render_event('unopim.admin.system_settings.edit.'.$entry['key'].'.before', ['entry' => $entry]) !!}

    <x-admin::form
        ajax
        :action="route('admin.settings.system.update', $entry['key'])"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex gap-4 justify-between items-center mt-3.5 max-sm:flex-wrap">
            <div class="flex flex-col gap-1">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang($entry['name'])
                </p>

                @isset($entry['info'])
                    <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">
                        @lang($entry['info'])
                    </p>
                @endisset
            </div>

            <button type="submit" class="primary-button">
                @lang('admin::app.settings.system-settings.save-btn')
            </button>
        </div>

        <div class="mt-6 grid gap-1.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            @php ($item = $entry)

            @foreach ($entry['fields'] as $field)
                @if ($field['type'] == 'blade' && view()->exists($path = $field['path']))
                    {!! view($path, compact('field', 'item'))->render() !!}
                @else
                    @include('admin::configuration.field-type')
                @endif
            @endforeach
        </div>
    </x-admin::form>

    {!! view_render_event('unopim.admin.system_settings.edit.'.$entry['key'].'.after', ['entry' => $entry]) !!}
</x-admin::layouts>
