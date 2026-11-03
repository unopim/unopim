@php
    $fields = collect([
        ['name' => 'logo_image', 'label' => 'logo', 'hints' => ['logo-size'], 'extensions' => \Webkul\Admin\Http\Requests\AppearanceForm::LOGO_EXTENSIONS, 'width' => '240px', 'dark' => false],
        ['name' => 'favicon', 'label' => 'favicon', 'hints' => ['favicon-size'], 'extensions' => \Webkul\Admin\Http\Requests\AppearanceForm::FAVICON_EXTENSIONS, 'width' => '120px', 'dark' => false],
        ['name' => 'logo_image_dark', 'label' => 'dark-logo', 'hints' => ['logo-size', 'dark-logo-info'], 'extensions' => \Webkul\Admin\Http\Requests\AppearanceForm::LOGO_EXTENSIONS, 'width' => '240px', 'dark' => true],
        ['name' => 'favicon_dark', 'label' => 'dark-favicon', 'hints' => ['favicon-size', 'dark-favicon-info'], 'extensions' => \Webkul\Admin\Http\Requests\AppearanceForm::FAVICON_EXTENSIONS, 'width' => '120px', 'dark' => true],
    ])->map(function (array $field): array {
        $value = core()->getConfigData('general.design.admin_logo.'.$field['name']);

        $field['images'] = $value ? [['id' => $field['name'], 'url' => Storage::url($value), 'value' => $value]] : [];

        return $field;
    });
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
        @foreach ($fields as $field)
            <div class="flex gap-4 flex-col">
                <x-admin::form.control-group class="!mb-0 shrink-0">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.appearance.'.$field['label'])
                    </x-admin::form.control-group.label>

                    <x-admin::media.image
                        :name="$field['name']"
                        :show-suggestions="false"
                        :accepted-extensions="$field['extensions']"
                        :width="$field['width']"
                        height="120px"
                        object-fit="contain"
                        :dark-surface="$field['dark']"
                        :uploaded-images="$field['images']"
                    />

                    <x-admin::form.control-group.error :control-name="$field['name']" />
                </x-admin::form.control-group>

                <p class="text-xs text-gray-600 dark:text-gray-300 leading-[140%]">
                    @foreach ($field['hints'] as $hint)
                        <span class="block">@lang('admin::app.settings.appearance.'.$hint)</span>
                    @endforeach
                </p>
            </div>
        @endforeach
    </div>
</x-admin::page>
