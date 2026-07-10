<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('admin::app.settings.data-transfer.exports.index.title')">
        <x-slot:actions>
            <!-- Create New Tax Rate Button -->
            @if (bouncer()->hasPermission('data_transfer.export.create'))
                <a href="{{ route('admin.settings.data_transfer.exports.create') }}" class="primary-button">
                    @lang('admin::app.settings.data-transfer.exports.index.button-title')
                </a>
            @endif
        </x-slot>
    </x-admin::page-header>

    <x-admin::datagrid :src="route('admin.settings.data_transfer.exports.index')"/>
</x-admin::layouts>