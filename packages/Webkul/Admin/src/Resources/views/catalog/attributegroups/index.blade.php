<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.attribute-groups.index.title')
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('admin::app.catalog.attribute-groups.index.title')"
        :sticky="false"
    >
        <x-slot:actions>
            @if (bouncer()->hasPermission('catalog.attribute_groups.create'))
                <x-admin::catalog.quick-create-modal
                    id="attributeGroupCreateModal"
                    :action="route('admin.catalog.attribute.groups.store')"
                    :button-label="trans('admin::app.catalog.attribute-groups.index.create-btn')"
                    :title="trans('admin::app.catalog.attribute-groups.create.title')"
                    :name-label="trans('admin::app.catalog.attribute-groups.index.datagrid.name')"
                    :name-placeholder="trans('admin::app.catalog.attribute-groups.index.datagrid.name')"
                    :code-label="trans('admin::app.catalog.attribute-groups.create.code')"
                    :code-placeholder="trans('admin::app.catalog.attribute-groups.create.code')"
                    :save-label="trans('admin::app.catalog.attribute-groups.create.save-btn')"
                />
            @endif
        </x-slot>
    </x-admin::layouts.edit-page-header>

    {!! view_render_event('unopim.admin.catalog.attribute.groups.list.before') !!}

    <x-admin::datagrid :src="route('admin.catalog.attribute.groups.index')" />

    {!! view_render_event('unopim.admin.catalog.attribute.groups.list.after') !!}

</x-admin::layouts>
