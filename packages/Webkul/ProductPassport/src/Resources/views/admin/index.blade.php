<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.publications.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('passport::app.publications.index.title')" />

    @include('passport::admin.partials.tabs', ['active' => 'passports'])

    <x-admin::datagrid :src="route('admin.catalog.passports.index')" />
</x-admin::layouts>
