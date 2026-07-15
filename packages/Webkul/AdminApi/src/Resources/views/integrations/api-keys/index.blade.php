@php
    $adminUsers = app(\Webkul\User\Repositories\AdminRepository::class)
        ->all(['id', 'name', 'email'])
        ->map(fn ($admin) => [
            'id'    => $admin->id,
            'label' => $admin->name.' ('.$admin->email.')',
        ])
        ->values()
        ->toJson();

    $permissionTypes = collect((new \Webkul\AdminApi\Models\Apikey)->getPermissionTypes())
        ->toJson();
@endphp

<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.configuration.integrations.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('admin::app.configuration.integrations.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('configuration.integrations.create')) 
                <button
                    type="button"
                    class="primary-button"
                    @click="$refs.integrationCreateModal.toggle()"
                >
                    @lang('admin::app.configuration.integrations.index.create-btn')
                </button>
            @endif
        </x-slot>
    </x-admin::layouts.page-header>

    @if (bouncer()->hasPermission('configuration.integrations.create'))
        <x-admin::form
            ajax
            :track-dirty="false"
            :action="route('admin.configuration.integrations.store')"
        >
            <x-admin::modal ref="integrationCreateModal">
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @lang('admin::app.configuration.integrations.create.title')
                    </p>
                </x-slot>

                <x-slot:content>
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.integrations.create.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            rules="required"
                            :label="trans('admin::app.configuration.integrations.create.name')"
                            :placeholder="trans('admin::app.configuration.integrations.create.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.integrations.create.assign-user')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="admin_id"
                            rules="required"
                            :label="trans('admin::app.configuration.integrations.create.assign-user')"
                            :placeholder="trans('admin::app.configuration.integrations.create.assign-user')"
                            :options="$adminUsers"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="admin_id" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.integrations.create.permissions')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="permission_type"
                            rules="required"
                            :label="trans('admin::app.configuration.integrations.create.permissions')"
                            :placeholder="trans('admin::app.configuration.integrations.create.permissions')"
                            :options="$permissionTypes"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="permission_type" />
                    </x-admin::form.control-group>
                </x-slot>

                <x-slot:footer>
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.configuration.integrations.create.save-btn')
                    </button>
                </x-slot>
            </x-admin::modal>
        </x-admin::form>
    @endif

    {!! view_render_event('unopim.admin.configuration.integrations.list.before') !!}
    
    <x-admin::datagrid src="{{ route('admin.configuration.integrations.index') }}" />

    {!! view_render_event('unopim.admin.configuration.integrations.list.after') !!}

</x-admin::layouts>
