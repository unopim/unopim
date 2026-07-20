<x-admin::layouts.with-history>
    <x-slot:entityName>
        role
    </x-slot>

    <x-slot:title>
        @lang('admin::app.settings.roles.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.settings.roles.edit.title')"
            :back-url="route('admin.settings.roles.index')"
            :back-label="trans('admin::app.settings.roles.edit.back-btn')"
            :save-label="trans('admin::app.settings.roles.edit.save-btn')"
            form="role-edit-form"
            :sticky="false"
        />
    </x-slot>

    {!! view_render_event('unopim.admin.settings.roles.edit.before') !!}

    <v-edit-user-role></v-edit-user-role>

    {!! view_render_event('unopim.admin.settings.roles.edit.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-user-role-template"
        >
            <div>
                <x-admin::form
                    id="role-edit-form"
                    ajax
                    method="PUT"
                    :action="route('admin.settings.roles.update', $role->id)"
                >

                {!! view_render_event('unopim.admin.settings.roles.edit.edit_form_controls.before') !!}

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                        {!! view_render_event('unopim.admin.settings.roles.edit.card.access-control.before') !!}

                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.settings.roles.edit.access-control')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.roles.edit.permissions')
                                </x-admin::form.control-group.label>
       
                                @php
                                    $options = json_encode([
                                        [
                                            'id'          => 'custom',
                                            'label'       => __('admin::app.settings.roles.edit.custom'),
                                            'description' => __('admin::app.settings.roles.edit.custom-description'),
                                        ],
                                        [
                                            'id'          => 'all',
                                            'label'       => __('admin::app.settings.roles.edit.all'),
                                            'description' => __('admin::app.settings.roles.edit.all-description'),
                                        ]
                                    ]);
                                    
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="permission_type"
                                    name="permission_type"
                                    rules="required"
                                    :options="$options"
                                    v-model="permission_type"
                                    :label="trans('admin::app.settings.roles.edit.permissions')"
                                    :placeholder="trans('admin::app.settings.roles.edit.permissions')"
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
                                    :value="json_encode($role->permissions)"
                                    :fallback-locale="config('app.fallback_locale')"
                                />
                            </div>
                        </div>

                        {!! view_render_event('unopim.admin.settings.roles.edit.card.access-control.after') !!}

                    </div>

                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

                        {!! view_render_event('unopim.admin.settings.roles.edit.card.accordion.general.before') !!}

                        <x-admin::accordion>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.settings.roles.edit.general')
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.roles.edit.name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="name"
                                        name="name"
                                        rules="required"
                                        value="{{ old('name') ?: $role->name }}"
                                        :label="trans('admin::app.settings.roles.edit.name')"
                                        :placeholder="trans('admin::app.settings.roles.edit.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="!mb-0">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.roles.edit.description')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        id="description"
                                        name="description"
                                        value="{{ old('description') ?: $role->description }}"
                                        :label="trans('admin::app.settings.roles.edit.description')"
                                        :placeholder="trans('admin::app.settings.roles.edit.description')"
                                    />

                                    <x-admin::form.control-group.error control-name="description" />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.settings.roles.edit.card.accordion.general.after') !!}

                    </div>
                </div>

                {!! view_render_event('unopim.admin.settings.roles.edit.edit_form_controls.after') !!}

                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-edit-user-role', {
                template: '#v-edit-user-role-template',

                data() {
                    return {
                        permission_type: "{{ $role->permission_type }}",
                        selected_permission_type: "{{ $role->permission_type }}",
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
</x-admin::layouts.with-history>
