@php
    $rootCategories = app(\Webkul\Category\Repositories\CategoryRepository::class)
        ->getRootCategories()
        ->toJson();

    $locales = core()->getAllActiveLocales()->toJson();

    $currencies = collect(core()->getAllActiveCurrencies())->values()->toJson();
@endphp

<x-admin::layouts>

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.channels.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('admin::app.settings.channels.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('settings.channels.create'))
                <x-admin::catalog.quick-create-modal
                    id="channelCreateModal"
                    :action="route('admin.settings.channels.store')"
                    :button-label="trans('admin::app.settings.channels.index.create-btn')"
                    :title="trans('admin::app.settings.channels.create.title')"
                    :name-label="trans('admin::app.settings.channels.create.name')"
                    :name-placeholder="trans('admin::app.settings.channels.create.name')"
                    :code-label="trans('admin::app.settings.channels.create.code')"
                    :code-placeholder="trans('admin::app.settings.channels.create.code')"
                    :root-category-label="trans('admin::app.settings.channels.create.root-category')"
                    :root-category-placeholder="trans('admin::app.settings.channels.create.select-root-category')"
                    :root-category-options="$rootCategories"
                    :locales-label="trans('admin::app.settings.channels.create.locales')"
                    :locales-placeholder="trans('admin::app.settings.channels.edit.select-locales')"
                    :locales-options="$locales"
                    :currencies-label="trans('admin::app.settings.channels.create.currencies')"
                    :currencies-placeholder="trans('admin::app.settings.channels.edit.select-currencies')"
                    :currencies-options="$currencies"
                    :save-label="trans('admin::app.settings.channels.create.save-btn')"
                />
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    {!! view_render_event('unopim.settings.channels.list.before') !!}
    
    <x-admin::datagrid src="{{ route('admin.settings.channels.index') }}" />

    {!! view_render_event('unopim.settings.channels.list.after') !!}

</x-admin::layouts>
