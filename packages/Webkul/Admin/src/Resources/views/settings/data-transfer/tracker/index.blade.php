<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.tracker.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.settings.data-transfer.tracker.index.title')" />

    <x-admin::datagrid :src="route('admin.settings.data_transfer.tracker.index')"/>
</x-admin::layouts>