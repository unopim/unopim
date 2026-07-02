<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.appearance.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.settings.appearance.update')"
        enctype="multipart/form-data"
        method="PUT"
    >
        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.settings.appearance.title')
            </p>

            <button
                type="submit"
                class="primary-button"
            >
                @lang('admin::app.settings.appearance.save-btn')
            </button>
        </div>

        <div class="mt-4 p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('admin::app.settings.appearance.section-title')
            </p>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <p class="mb-2 text-sm text-gray-700 dark:text-gray-200 font-medium">
                        @lang('admin::app.settings.appearance.logo')
                    </p>

                    <div class="mb-3 p-3 border rounded bg-gray-50 dark:bg-cherry-800 dark:border-gray-700 min-h-[72px] flex items-center">
                        @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                            <img
                                src="{{ Storage::url($logo) }}"
                                alt="Admin Logo"
                                class="max-h-12 object-contain"
                            />
                        @else
                            <span class="text-sm text-gray-500">@lang('admin::app.settings.appearance.no-logo')</span>
                        @endif
                    </div>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.control
                            type="file"
                            name="logo_image"
                            accept="image/*"
                            :label="trans('admin::app.settings.appearance.logo')"
                        />
                        <x-admin::form.control-group.error control-name="logo_image" />
                    </x-admin::form.control-group>

                    <p class="mt-2 text-xs text-gray-500">
                        @lang('admin::app.settings.appearance.logo-size')
                    </p>
                </div>

                <div>
                    <p class="mb-2 text-sm text-gray-700 dark:text-gray-200 font-medium">
                        @lang('admin::app.settings.appearance.favicon')
                    </p>

                    <div class="mb-3 p-3 border rounded bg-gray-50 dark:bg-cherry-800 dark:border-gray-700 min-h-[72px] flex items-center">
                        @if ($favicon = core()->getConfigData('general.design.admin_logo.favicon'))
                            <img
                                src="{{ Storage::url($favicon) }}"
                                alt="Favicon"
                                class="w-8 h-8 object-contain"
                            />
                        @else
                            <span class="text-sm text-gray-500">@lang('admin::app.settings.appearance.no-favicon')</span>
                        @endif
                    </div>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.control
                            type="file"
                            name="favicon"
                            accept=".ico,image/png,image/svg+xml,image/webp"
                            :label="trans('admin::app.settings.appearance.favicon')"
                        />
                        <x-admin::form.control-group.error control-name="favicon" />
                    </x-admin::form.control-group>

                    <p class="mt-2 text-xs text-gray-500">
                        @lang('admin::app.settings.appearance.favicon-size')
                    </p>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
