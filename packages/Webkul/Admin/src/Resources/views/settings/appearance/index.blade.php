@php
    $logo = core()->getConfigData('general.design.admin_logo.logo_image');

    $favicon = core()->getConfigData('general.design.admin_logo.favicon');
@endphp

<x-admin::page
    :title="trans('admin::app.settings.appearance.title')"
    :subtitle="trans('admin::app.settings.appearance.section-info')"
    :back="route('admin.settings.system.index')"
    :action="route('admin.settings.appearance.update')"
    method="PUT"
    enctype="multipart/form-data"
>
    <x-slot:actions>
        <button type="submit" class="primary-button">
            @lang('admin::app.settings.appearance.save-btn')
        </button>
    </x-slot>

    <div class="mt-6 grid grid-cols-1 gap-6 p-4 bg-white dark:bg-cherry-900 box-shadow rounded sm:grid-cols-2">
        {{-- Logo --}}
        <div class="flex gap-4 flex-col">
            <x-admin::form.control-group class="!mb-0 shrink-0">
                <x-admin::form.control-group.label>
                    @lang('admin::app.settings.appearance.logo')
                </x-admin::form.control-group.label>

                <x-admin::media.image
                    name="logo_image"
                    :show-suggestions="false"
                    :accepted-extensions="\Webkul\Admin\Http\Requests\AppearanceForm::LOGO_EXTENSIONS"
                    :max-size="\Webkul\Admin\Http\Requests\AppearanceForm::LOGO_MAX_SIZE"
                    width="240px"
                    height="120px"
                    object-fit="contain"
                    :uploaded-images="$logo ? [['id' => 'logo_image', 'url' => Storage::url($logo), 'value' => $logo]] : []"
                />

                <x-admin::form.control-group.error control-name="logo_image" />

                @if ($errors->has('logo_image.*'))
                    <p class="mt-2 text-red-600 text-xs italic">{{ $errors->first('logo_image.*') }}</p>
                @endif
            </x-admin::form.control-group>

            <p class="text-xs text-gray-600 dark:text-gray-300 leading-[140%]">
                @lang('admin::app.settings.appearance.logo-size')
            </p>
        </div>

        {{-- Favicon --}}
        <div class="flex gap-4 flex-col">
            <x-admin::form.control-group class="!mb-0 shrink-0">
                <x-admin::form.control-group.label>
                    @lang('admin::app.settings.appearance.favicon')
                </x-admin::form.control-group.label>

                <x-admin::media.image
                    name="favicon"
                    :show-suggestions="false"
                    :accepted-extensions="\Webkul\Admin\Http\Requests\AppearanceForm::FAVICON_EXTENSIONS"
                    :max-size="\Webkul\Admin\Http\Requests\AppearanceForm::FAVICON_MAX_SIZE"
                    width="120px"
                    height="120px"
                    object-fit="contain"
                    :uploaded-images="$favicon ? [['id' => 'favicon', 'url' => Storage::url($favicon), 'value' => $favicon]] : []"
                />

                <x-admin::form.control-group.error control-name="favicon" />

                @if ($errors->has('favicon.*'))
                    <p class="mt-2 text-red-600 text-xs italic">{{ $errors->first('favicon.*') }}</p>
                @endif
            </x-admin::form.control-group>

            <p class="text-xs text-gray-600 dark:text-gray-300 leading-[140%]">
                @lang('admin::app.settings.appearance.favicon-size')
            </p>
        </div>
    </div>
</x-admin::page>
