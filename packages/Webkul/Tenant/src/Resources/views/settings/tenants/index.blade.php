<x-admin::layouts>
    <x-slot:title>
        @lang('tenant::app.tenants.index.title')
    </x-slot>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('tenant::app.tenants.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            @if (bouncer()->hasPermission('settings.tenants.create'))
                <a
                    href="{{ route('admin.settings.tenants.create') }}"
                    class="primary-button"
                >
                    @lang('tenant::app.tenants.index.create-btn')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid src="{{ route('admin.settings.tenants.index') }}" />
</x-admin::layouts>
