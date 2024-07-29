<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.tracker.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.settings.data-transfer.tracker.index.title')
        </p> 
    </div>

    <x-admin::datagrid :src="route('admin.settings.data_transfer.tracker.index')"/>
</x-admin::layouts>