@php
    $channels = core()->getAllChannels();

    $currentChannel = core()->getRequestedChannel();

    $currentLocale = core()->getRequestedLocale();
@endphp

<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @if ($items = Arr::get($config->items, request()->route('slug') . '.children'))
            @foreach ($items as $key => $item)
                @if ( $key == request()->route('slug2'))
                    {{ $title = trans($item['name']) }}
                @endif
            @endforeach
        @endif
    </x-slot>

    <!-- Configuration form fields -->
    <x-admin::form 
        action="" 
        enctype="multipart/form-data"
    >
        <!-- Save Inventory -->
        <div class="flex gap-4 justify-between items-center mt-3.5 max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                {{ $title }}
            </p>

            <!-- Save Inventory -->
            <div class="flex gap-x-2.5 items-center">
                <button 
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.configuration.index.save-btn')
                </button>
            </div>
        </div>
        @if ($groups)
            <div class="grid grid-cols-[1fr_2fr] gap-10 mt-6 max-xl:flex-wrap">
                @foreach ($groups as $key => $item)
                    <div class="grid gap-2.5 content-start">
                        <p class="text-base text-gray-600 dark:text-gray-300 font-semibold">
                            @lang($item['name'])
                        </p>

                        <p class="text-gray-600 dark:text-gray-300 leading-[140%]">
                            @lang($item['info'] ?? '')
                        </p>
                    </div>

                    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                        @foreach ($item['fields'] as $field)
                            @include ('admin::configuration.field-type')
                        
                            @php ($hint = $field['title'] . '-hint')

                            @if ($hint !== __($hint))
                                <label 
                                    for="@lang($hint)"
                                    class="block leading-5 text-xs text-gray-600 dark:text-gray-300 font-medium"
                                >
                                    @lang($hint)
                                </label>
                            @endIf
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endIf
    </x-admin::form>
</x-admin::layouts>
