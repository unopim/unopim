<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.templates.index.title')
    </x-slot>

    <x-admin::page-header :title="trans('passport::app.templates.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('catalog.passport.template.create'))
                <a
                    href="{{ route('admin.catalog.passports.templates.create') }}"
                    class="primary-button"
                >
                    @lang('passport::app.templates.index.create-btn')
                </a>
            @endif
        </x-slot>
    </x-admin::page-header>

    @include('passport::admin.partials.tabs', ['active' => 'templates'])

    <x-admin::datagrid :src="route('admin.catalog.passports.templates.index')" />
</x-admin::layouts>
