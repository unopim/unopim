<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.settings.data-transfer.exports.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Create New Tax Rate Button -->
            @if (bouncer()->hasPermission('settings.data_transfer.exports.create'))
                <a href="{{ route('admin.settings.data_transfer.exports.create') }}" class="primary-button">
                    @lang('admin::app.settings.data-transfer.exports.index.button-title')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.settings.data_transfer.exports.index')"/>
</x-admin::layouts>