<x-admin::layouts>
    <x-slot:title>
        @lang('resource::app.search')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('resource::app.search')">
        <x-slot:actions>
            @if (bouncer()->hasPermission($resource['aclPrefix'].'.create'))
                <a
                    href="{{ $resource['urls']['create'] }}"
                    class="primary-button"
                >
                    @lang('resource::app.create')
                </a>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    {!! view_render_event('unopim.resource.index.list.before') !!}

    <x-admin::datagrid :src="$datagridSrc" />

    {!! view_render_event('unopim.resource.index.list.after') !!}
</x-admin::layouts>
