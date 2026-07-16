@php
    $channels = core()->getAllChannels();

    $currentChannel = core()->getRequestedChannel();

    $currentLocale = core()->getRequestedLocale();

    $logo = core()->getConfigData('general.design.admin_logo.logo_image');

    $favicon = core()->getConfigData('general.design.admin_logo.favicon');
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.components.layouts.sidebar.system-settings')
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col gap-1 mt-3.5">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.components.layouts.sidebar.system-settings')
        </p>

        <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">
            @lang('admin::app.settings.system-settings.info')
        </p>
    </div>

    <div class="grid gap-4 mt-5">
        {{-- Appearance --}}
        <x-admin::accordion>
            <x-slot:header>
                <p class="text-base font-semibold text-gray-800 dark:text-slate-50">
                    @lang('admin::app.settings.appearance.title')
                </p>
            </x-slot>

            <x-slot:content>
                <x-admin::form
                    :action="route('admin.settings.appearance.update')"
                    enctype="multipart/form-data"
                    method="PUT"
                >
                    <div class="grid grid-cols-1 gap-6 p-4 bg-white dark:bg-cherry-900 box-shadow rounded sm:grid-cols-2">
                        {{-- Logo --}}
                        <div class="flex gap-4 flex-col">
                            <p class="text-sm font-semibold text-gray-800 dark:text-slate-50">
                                @lang('admin::app.settings.appearance.logo')
                            </p>

                            <x-admin::form.control-group class="!mb-0 shrink-0">
                                <x-admin::media.images
                                    name="logo_image"
                                    :allow-multiple="false"
                                    :show-suggestions="false"
                                    :show-upload-hint="false"
                                    width="240px"
                                    height="120px"
                                    object-fit="contain"
                                    :uploaded-images="$logo ? [['id' => 'logo_image', 'url' => Storage::url($logo), 'value' => $logo]] : []"
                                />

                                <x-admin::form.control-group.error control-name="logo_image" />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300 leading-[140%]">
                                @lang('admin::app.settings.appearance.logo-size')
                            </p>
                        </div>

                        {{-- Favicon --}}
                        <div class="flex gap-4 flex-col">
                            <p class="text-sm font-semibold text-gray-800 dark:text-slate-50">
                                @lang('admin::app.settings.appearance.favicon')
                            </p>

                            <x-admin::form.control-group class="!mb-0 shrink-0">
                                <x-admin::media.images
                                    name="favicon"
                                    :allow-multiple="false"
                                    :show-suggestions="false"
                                    :show-upload-hint="false"
                                    width="120px"
                                    height="120px"
                                    object-fit="contain"
                                    :uploaded-images="$favicon ? [['id' => 'favicon', 'url' => Storage::url($favicon), 'value' => $favicon]] : []"
                                />

                                <x-admin::form.control-group.error control-name="favicon" />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300 leading-[140%]">
                                @lang('admin::app.settings.appearance.favicon-size')
                            </p>
                        </div>
                    </div>
                </x-admin::form>
            </x-slot>
        </x-admin::accordion>

        {{-- SMTP / Email --}}
        @if ($smtpGroup)
            <x-admin::accordion>
                <x-slot:header>
                    <p class="text-base font-semibold text-gray-800 dark:text-slate-50">
                        @lang('admin::app.configuration.index.emails.configure.email-settings.title')
                    </p>
                </x-slot>

                <x-slot:content>
                    <x-admin::form
                        :action="route('admin.configuration.store', ['slug' => 'emails', 'slug2' => 'configure'])"
                        enctype="multipart/form-data"
                        :hide-save-when-tracked="true"
                    >
                        <div class="grid gap-1.5 p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            @php ($item = $smtpGroup)

                            @foreach ($smtpGroup['fields'] as $field)
                                @if ($field['type'] == 'blade' && view()->exists($path = $field['path']))
                                    {!! view($path, compact('field', 'item'))->render() !!}
                                @else
                                    @include('admin::configuration.field-type')
                                @endif
                            @endforeach

                            <div class="flex justify-end">
                                <button type="submit" class="primary-button">
                                    @lang('admin::app.configuration.index.save-btn')
                                </button>
                            </div>
                        </div>
                    </x-admin::form>
                </x-slot>
            </x-admin::accordion>
        @endif

        {{-- Debug --}}
        @if ($debugGroup)
            <x-admin::accordion>
                <x-slot:header>
                    <p class="text-base font-semibold text-gray-800 dark:text-slate-50">
                        @lang('admin::app.configuration.index.general.debug.settings.title')
                    </p>
                </x-slot>

                <x-slot:content>
                    <x-admin::form
                        :action="route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'debug'])"
                        enctype="multipart/form-data"
                    >
                        <div class="grid gap-1.5 p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-[140%] mb-2">
                                @lang('admin::app.configuration.index.general.debug.settings.info')
                            </p>

                            @php ($item = $debugGroup)

                            @foreach ($debugGroup['fields'] as $field)
                                @if ($field['type'] == 'blade' && view()->exists($path = $field['path']))
                                    {!! view($path, compact('field', 'item'))->render() !!}
                                @else
                                    @include('admin::configuration.field-type')
                                @endif
                            @endforeach

                            <div class="flex justify-end">
                                <button type="submit" class="primary-button">
                                    @lang('admin::app.configuration.index.save-btn')
                                </button>
                            </div>
                        </div>
                    </x-admin::form>
                </x-slot>
            </x-admin::accordion>
        @endif
    </div>
</x-admin::layouts>
