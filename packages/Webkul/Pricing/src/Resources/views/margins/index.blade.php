<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.margins.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('pricing::app.margins.index.title')
        </p>
    </div>

    {!! view_render_event('unopim.admin.pricing.margins.list.before') !!}

    <x-admin::datagrid :src="route('admin.pricing.margins.index')" />

    {!! view_render_event('unopim.admin.pricing.margins.list.after') !!}

</x-admin::layouts>
