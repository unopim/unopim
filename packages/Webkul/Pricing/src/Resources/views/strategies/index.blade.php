<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.strategies.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('pricing::app.strategies.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('pricing.strategies.create'))
                <a href="{{ route('admin.pricing.strategies.create') }}">
                    <div class="primary-button">
                        @lang('pricing::app.strategies.index.create-btn')
                    </div>
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.pricing.strategies.list.before') !!}

    <x-admin::datagrid :src="route('admin.pricing.strategies.index')" />

    {!! view_render_event('unopim.admin.pricing.strategies.list.after') !!}

</x-admin::layouts>
