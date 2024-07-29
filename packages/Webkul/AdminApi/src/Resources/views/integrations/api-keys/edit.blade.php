<x-admin::layouts.with-history>
    <x-slot:entityName>
        Apikey
    </x-slot>

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.configuration.integrations.edit.title')
    </x-slot>

    {!! view_render_event('unopim.admin.configuration.integrations.edit.before') !!}

    <!-- Edit API Integration for  -->
    <v-edit-user-api-integration></v-edit-user-api-integration>

    {!! view_render_event('unopim.admin.configuration.integrations.edit.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-user-api-integration-template"
        >
            <div>
                <x-admin::form
                    method="PUT"
                    :action="route('admin.configuration.integrations.update', $apiKey->id)"
                >

                {!! view_render_event('unopim.admin.configuration.integrations.edit.edit_form_controls.before') !!}

                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.configuration.integrations.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Cancel Button -->
                        <a
                            href="{{ route('admin.configuration.integrations.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.configuration.integrations.edit.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.configuration.integrations.edit.save-btn')
                        </button>
                    </div>
                </div>

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <!-- Left sub-component -->
                    
                    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                        {!! view_render_event('unopim.admin.configuration.integrations.edit.card.access-control.before') !!}

                        <!-- Access Control Input Fields -->
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                @lang('admin::app.configuration.integrations.edit.access-control')
                            </p>

                            <!-- Permission Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.configuration.integrations.edit.permissions')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="permission_type"
                                    name="permission_type"
                                    rules="required"
                                    :options="$permissionTypes"
                                    v-model="permission_type"
                                    :label="trans('admin::app.configuration.integrations.edit.permissions')"
                                    :placeholder="trans('admin::app.configuration.integrations.edit.permissions')"
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
                                    :value="json_encode($apiKey->permissions)"
                                    :fallback-locale="config('app.fallback_locale')"
                                />
                            </div>
                        </div>

                        {!! view_render_event('unopim.admin.configuration.integrations.edit.card.access-control.after') !!}
                    </div>



                    <!-- Right sub-component -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

                        {!! view_render_event('unopim.admin.configuration.integrations.edit.card.accordion.general.before') !!}

                        <x-admin::accordion>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.configuration.integrations.edit.general')
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot:content>
                                <!-- Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.integrations.edit.name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="name"
                                        name="name"
                                        v-model="name"
                                        rules="required"
                                        value="{{ old('name') ?: $apiKey->name }}"
                                        :label="trans('admin::app.configuration.integrations.edit.name')"
                                        :placeholder="trans('admin::app.configuration.integrations.edit.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>
                                <!-- User -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.integrations.create.assign-user')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        class="cursor-not-allowed"
                                        name="admin_id"
                                        rules="required"
                                        value="{{$apiKey->admins->name}}"
                                        readonly
                                        :label="trans('admin::app.configuration.integrations.edit.assign-user')"
                                        :placeholder="trans('admin::pp.configuration.integrations.edit.assign-user')"
                                    />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        <!-- Credentials -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.configuration.integrations.edit.credentials')
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot:content>
                                <!-- Client ID -->
                                <x-admin::form.control-group v-if="client_id">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.integrations.edit.client-id')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        class="cursor-not-allowed"
                                        id="client_id"
                                        name="client_id"
                                        :value="old('client_id')"
                                        v-model="client_id"
                                        readonly
                                        :label="trans('admin::app.configuration.integrations.edit.client-id')"
                                        :placeholder="trans('admin::app.configuration.integrations.edit.client-id')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <!-- Secret Key -->
                                <x-admin::form.control-group v-if="secret_key">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.configuration.integrations.edit.secret-key')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        class="cursor-not-allowed"
                                        id="secret_key"
                                        name="secret_key"
                                        :value="old('secret_key')"
                                        v-model="secret_key"
                                        readonly
                                        :label="trans('admin::app.configuration.integrations.edit.secret-key')"
                                        :placeholder="trans('admin::app.configuration.integrations.edit.secret-key')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <div class="flex gap-x-2.5 items-center" v-if="!oauth_client_id">
                                    <button
                                        type="button"
                                        class="primary-button"
                                        @click="generateKey"
                                    >
                                        @lang('admin::app.configuration.integrations.edit.generate-btn')
                                    </button>
                                </div>
                                <div class="flex gap-x-2.5 items-center" v-if="oauth_client_id">
                                    <button
                                        type="button"
                                        class="primary-button"
                                        @click="reGenerateSecretKey"
                                    >
                                        @lang('admin::app.configuration.integrations.edit.re-secret-btn')
                                    </button>
                                </div>
                            </x-slot>
                        </x-admin::accordion>

                        {!! view_render_event('unopim.admin.configuration.integrations.edit.card.accordion.general.after') !!}

                    </div>
                </div>

                {!! view_render_event('unopim.admin.configuration.integrations.edit.edit_form_controls.after') !!}

                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-edit-user-api-integration', {
                template: '#v-edit-user-api-integration-template',

                data() {
                    return {
                        permission_type: "{{ $apiKey->permission_type }}",
                        selected_permission_type: "{{ $apiKey->permission_type }}",
                        client_id: "{{ $client_id }}",
                        secret_key: "{{ $secret_key }}",
                        admin_id: "{{ $apiKey->admin_id }}",
                        name: "{{ $apiKey->name }}",
                        apiId: "{{ $apiKey->id }}",
                        oauth_client_id: "{{ $oauth_client_id }}",
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
                    },

                    generateKey() {
                        let formData = new FormData();
                        formData.append('admin_id', this.admin_id);
                        formData.append('name', this.name);
                        formData.append('apiId', this.apiId);
                        this.$axios.post("{{route('admin.configuration.integrations.generate_key')}}", formData)
                            .then((response) => {
                                this.client_id = response.data.client_id;
                                this.secret_key = response.data.secret_key;
                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('admin::app.configuration.integrations.generate-key-success')" });

                            })
                            .catch(error => {
                                
                            });

                    },

                    reGenerateSecretKey() {
                        let formData = new FormData();
                        formData.append('admin_id', this.admin_id);
                        formData.append('name', this.name);
                        formData.append('apiId', this.apiId);
                        formData.append('oauth_client_id', this.oauth_client_id);
                        this.$axios.post("{{route('admin.configuration.integrations.re_generate_secret_key')}}", formData)
                            .then((response) => {
                                this.secret_key = response.data.secret_key;
                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('admin::app.configuration.integrations.re-generate-secret-key-success')" });

                            })
                            .catch(error => {
                                
                            });
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
