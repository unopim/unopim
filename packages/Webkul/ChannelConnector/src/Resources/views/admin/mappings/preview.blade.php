<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.mappings.preview') - {{ $connector->name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.mappings.preview') - {{ $connector->name }}
        </p>

        <a href="{{ route('admin.channel_connector.mappings.index', $connector->code) }}" class="transparent-button">
            @lang('channel_connector::app.general.back')
        </a>
    </div>

    <div class="mt-3.5">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-gray-600 dark:text-gray-400">@lang('channel_connector::app.mappings.fields.unopim-attribute')</th>
                        <th class="px-4 py-2 text-gray-600 dark:text-gray-400">@lang('channel_connector::app.mappings.fields.channel-field')</th>
                        <th class="px-4 py-2 text-gray-600 dark:text-gray-400">@lang('channel_connector::app.mappings.fields.direction')</th>
                        <th class="px-4 py-2 text-gray-600 dark:text-gray-400">@lang('channel_connector::app.mappings.fields.locale-mapping')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mappings as $mapping)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-2 text-gray-800 dark:text-white">{{ $mapping->unopim_attribute_code }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-white">{{ $mapping->channel_field }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-700">
                                    {{ trans("channel_connector::app.mappings.direction.{$mapping->direction}") }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                @if($mapping->locale_mapping)
                                    @foreach($mapping->locale_mapping as $from => $to)
                                        <span class="text-xs">{{ $from }} &rarr; {{ $to }}</span><br>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layouts>
