<x-admin::layouts>
    <x-slot:title>
        @lang('tenant::app.tenants.show.title') — {{ $tenant->name }}
    </x-slot>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            {{ $tenant->name }}
        </p>

        <div class="flex gap-x-2.5 items-center">
            <a
                href="{{ route('admin.settings.tenants.index') }}"
                class="transparent-button"
            >
                @lang('tenant::app.tenants.show.back-btn')
            </a>

            @if (bouncer()->hasPermission('settings.tenants.edit'))
                <a
                    href="{{ route('admin.settings.tenants.edit', $tenant->id) }}"
                    class="primary-button"
                >
                    @lang('tenant::app.tenants.show.edit-btn')
                </a>
            @endif
        </div>
    </div>

    <div class="flex gap-2.5 mt-3.5">
        <div class="flex flex-col gap-2 flex-1 overflow-auto">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('tenant::app.tenants.show.domain')</p>
                        <p class="text-gray-800 dark:text-white font-semibold">{{ $tenant->domain }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('tenant::app.tenants.show.status')</p>
                        <p class="font-semibold">{{ $tenant->status }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">@lang('tenant::app.tenants.show.created-at')</p>
                        <p class="text-gray-800 dark:text-white">{{ $tenant->created_at }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
