<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.costs.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <!-- Title -->
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('pricing::app.costs.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('pricing.costs.create'))
                <a href="{{ route('admin.pricing.costs.create') }}">
                    <div class="primary-button">
                        @lang('pricing::app.costs.index.create-btn')
                    </div>
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.pricing.costs.list.before') !!}

    <x-admin::datagrid :src="route('admin.pricing.costs.index')" />

    {!! view_render_event('unopim.admin.pricing.costs.list.after') !!}

</x-admin::layouts>
