@php
    $logo = core()->getConfigData('general.design.admin_logo.logo_image');

    $favicon = core()->getConfigData('general.design.admin_logo.favicon');
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.appearance.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.settings.appearance.update')"
        enctype="multipart/form-data"
        method="PUT"
    >
        <div class="flex gap-4 justify-between items-center mt-3.5 max-sm:flex-wrap">
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('admin.settings.system.index') }}"
                    class="flex items-center justify-center w-9 h-9 rounded-md border border-gray-200 dark:border-cherry-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 transition-all shrink-0"
                    aria-label="{{ trans('admin::app.settings.system-settings.back') }}"
                >
                    <span class="icon-left rtl:rotate-180 text-xl"></span>
                </a>

                <div class="flex flex-col gap-1">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.settings.appearance.title')
                    </p>

                    <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">
                        @lang('admin::app.settings.appearance.section-info')
                    </p>
                </div>
            </div>

            <button
                type="submit"
                class="primary-button"
            >
                @lang('admin::app.settings.appearance.save-btn')
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 p-4 bg-white dark:bg-cherry-900 box-shadow rounded sm:grid-cols-2">
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
</x-admin::layouts>
