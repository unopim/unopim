<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.templates.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('passport::app.templates.index.title')">
        <x-slot:actions>
            @include('passport::admin.templates.create')
        </x-slot>
    </x-admin::page-header>

    @include('passport::admin.partials.tabs', ['active' => 'templates'])

    <x-admin::datagrid :src="route('admin.catalog.passports.templates.index')" />
</x-admin::layouts>
