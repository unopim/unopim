<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.sync.index.title')
    </x-slot>

    {!! view_render_event('unopim.order.sync.list.before') !!}

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.sync.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Manual Sync Button -->
            @if (bouncer()->hasPermission('order.sync.create'))
                <a
                    href="{{ route('admin.order.sync.manual') }}"
                    class="primary-button"
                >
                    @lang('order::app.admin.sync.index.sync-now')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid src="{{ route('admin.order.sync.index') }}" />

    {!! view_render_event('unopim.order.sync.list.after') !!}

</x-admin::layouts>
