<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.configuration.integrations.create.title')
    </x-slot>

    {!! view_render_event('unopim.admin.configuration.integrations.create.before') !!}

    <!-- Create Role for -->
    <v-create-user-role></v-create-user-role>

    {!! view_render_event('unopim.admin.configuration.integrations.create.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-user-role-template"
        >
            <div>
                <x-admin::form :action="route('admin.configuration.integrations.store')">
                    {!! view_render_event('unopim.admin.configuration.integrations.create.create_form_controls.before') !!}

                    <div class="flex justify-between items-center">
                        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                            @lang('admin::app.configuration.integrations.create.title')
                        </p>

                        <div class="flex gap-x-2.5 items-center">
                            <!-- Cancel Button -->
                            <a
                                href="{{ route('admin.configuration.integrations.index') }}"
                                class="transparent-button"
                            >
                                @lang('admin::app.configuration.integrations.create.back-btn')
                            </a>

                            <!-- Save Button -->
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.configuration.integrations.create.save-btn')
                            </button>
                        </div>
                    </div>

                    <!-- body content -->
                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <!-- Left sub-component -->
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                            {!! view_render_event('unopim.admin.configuration.integrations.create.card.access_control.before') !!}
                            <!-- Access Control Input Fields -->
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    @lang('admin::app.configuration.integrations.create.access-control')
                                </p>

                                <!-- Permission Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.configuration.integrations.create.permissions')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="permission_type"
                                        name="permission_type"
                                        rules="required"
                                        :options="$permissionTypes"
                                        v-model="permission_type"
                                        :label="trans('admin::app.configuration.integrations.create.permissions')"
                                        :placeholder="trans('admin::app.configuration.integrations.create.permissions')"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="permission_type" />
                                </x-admin::form.control-group>

                                <div v-if="permission_type == 'custom' || selected_permission_type == 'custom'">
                                    <x-admin::tree.view
                                        input-type="checkbox"
                                        value-field="key"
                                        id-field="key"
                                        :items="json_encode($acl->items)"
                                        :fallback-locale="config('app.fallback_locale')"
                                    />
                                </div>
                            </div>
                            {!! view_render_event('unopim.admin.configuration.integrations.create.card.access_control.after') !!}
                        </div>

                        <!-- Right sub-component -->
                        <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

                            {!! view_render_event('unopim.admin.configuration.integrations.create.card.accordion.general.before') !!}

                            <x-admin::accordion>
                                <x-slot:header>
                                    <div class="flex items-center justify-between">
                                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                            @lang('admin::app.configuration.integrations.create.general')
                                        </p>
                                    </div>
                                </x-slot>

                                <x-slot:content>
                                    <!-- Name -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.configuration.integrations.create.name')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            id="name"
                                            name="name"
                                            rules="required"
                                            value="{{ old('name') }}"
                                            :label="trans('admin::app.configuration.integrations.create.name')"
                                            :placeholder="trans('admin::app.configuration.integrations.create.name')"
                                        />

                                        <x-admin::form.control-group.error control-name="name" />
                                    </x-admin::form.control-group>

                                    <!-- User -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.configuration.integrations.create.assign-user')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            id="admin_id"
                                            class="cursor-pointer"
                                            name="admin_id"
                                            rules="required"
                                            :value="old('admin_id')"
                                            v-model="attributeType"
                                            :label="trans('admin::app.configuration.integrations.create.assign-user')"
                                            :options="$adminUsers"
                                            track-by="id"
                                            label-by="name"
                                        >
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="admin_id" />
                                    </x-admin::form.control-group>
                                </x-slot>
                            </x-admin::accordion>

                            {!! view_render_event('unopim.admin.configuration.integrations.create.card.accordion.general.after') !!}

                        </div>
                    </div>
                    {!! view_render_event('unopim.admin.configuration.integrations.create.create_form_controls.after') !!}
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-user-role', {
                template: '#v-create-user-role-template',

                data() {
                    
                    return {
                        permission_type: 'all',
                        selected_permission_type: 'all',
                        client_id: null,
                        secret_key: null,
                    };
                },
                watch: {
                    permission_type(value) {
                        this.selected_permission_type = this.parseValue(value)?.id;
                    },
                },
                methods: {
                    parseValue(value) {  
                        try {
                            return value ? JSON.parse(value) : null;
                        } catch (error) {
                            return value;
                        }
                    }
                }

            })
        </script>
    @endPushOnce
</x-admin::layouts>
