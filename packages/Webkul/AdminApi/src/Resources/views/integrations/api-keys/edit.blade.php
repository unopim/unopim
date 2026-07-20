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
                    ajax
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
                                    <p class="text-base text-gray-800 dark:text-white font-semibold">
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
                                        :placeholder="trans('admin::app.configuration.integrations.edit.assign-user')"
                                    />
                                </x-admin::form.control-group>
                            </x-slot>
                        </x-admin::accordion>

                        <!-- Credentials -->
                        <x-admin::accordion>
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.configuration.integrations.edit.credentials')
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot:content>
                                {{-- Credential values are static v-text displays, not named form controls, so they never round-trip through FormData / the update request. --}}
                                <div
                                    v-if="oauth_client_id"
                                    class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-3 py-2.5 text-xs text-gray-700 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300"
                                >
                                    @lang('admin::app.configuration.integrations.edit.credentials-info')
                                </div>

                                <div class="flex gap-x-2.5 items-center" v-if="!oauth_client_id">
                                    <button
                                        type="button"
                                        class="primary-button"
                                        @click="generateKey"
                                    >
                                        @lang('admin::app.configuration.integrations.edit.generate-btn')
                                    </button>
                                </div>

                                <div v-if="oauth_client_id" class="divide-y divide-gray-200 dark:divide-cherry-800">
                                    <!-- Client ID -->
                                    <div class="flex items-start justify-between gap-2 py-3">
                                        <div class="min-w-0">
                                            <p class="mb-1 text-xs font-semibold text-primary-600 dark:text-primary-300">
                                                @lang('admin::app.configuration.integrations.edit.client-id')
                                            </p>

                                            <p class="break-all select-all text-sm text-gray-600 dark:text-gray-300" v-text="client_id"></p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <button
                                                type="button"
                                                class="rounded p-1.5 text-gray-500 transition-all hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
                                                :title="'@lang('admin::app.configuration.integrations.edit.copy')'"
                                                @click="copy(client_id)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                    <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Secret -->
                                    <div class="flex items-start justify-between gap-2 py-3">
                                        <div class="min-w-0">
                                            <p class="mb-1 text-xs font-semibold text-primary-600 dark:text-primary-300">
                                                @lang('admin::app.configuration.integrations.edit.secret-key')
                                            </p>

                                            <p class="break-all select-all text-sm text-gray-600 dark:text-gray-300" v-text="secret_key"></p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <button
                                                type="button"
                                                class="rounded p-1.5 text-gray-500 transition-all hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
                                                :title="'@lang('admin::app.configuration.integrations.edit.re-secret-btn')'"
                                                @click="reGenerateSecretKey"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                    <path d="M23 4v6h-6"></path>
                                                    <path d="M1 20v-6h6"></path>
                                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                class="rounded p-1.5 text-gray-500 transition-all hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
                                                :title="'@lang('admin::app.configuration.integrations.edit.copy')'"
                                                @click="copy(secret_key)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                    <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- API Username -->
                                    <div class="flex items-start justify-between gap-2 py-3">
                                        <div class="min-w-0">
                                            <p class="mb-1 text-xs font-semibold text-primary-600 dark:text-primary-300">
                                                @lang('admin::app.configuration.integrations.edit.api-username')
                                            </p>

                                            <p class="break-all select-all text-sm text-gray-600 dark:text-gray-300" v-text="api_username"></p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <button
                                                type="button"
                                                class="rounded p-1.5 text-gray-500 transition-all hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
                                                :title="'@lang('admin::app.configuration.integrations.edit.copy')'"
                                                @click="copy(api_username)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                    <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- API Password -->
                                    <div class="flex items-start justify-between gap-2 py-3">
                                        <div class="min-w-0">
                                            <p class="mb-1 text-xs font-semibold text-primary-600 dark:text-primary-300">
                                                @lang('admin::app.configuration.integrations.edit.api-password')
                                            </p>

                                            <p v-if="api_password" class="break-all select-all font-mono text-sm text-gray-600 dark:text-gray-300" v-text="api_password"></p>
                                            <span v-else class="font-mono text-sm text-gray-400 dark:text-gray-500">••••••••••••</span>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <button
                                                type="button"
                                                v-if="api_password"
                                                class="rounded p-1.5 text-gray-500 transition-all hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
                                                :title="'@lang('admin::app.configuration.integrations.edit.copy')'"
                                                @click="copy(api_password)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                    <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                class="rounded p-1.5 text-gray-500 transition-all hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
                                                :title="'@lang('admin::app.configuration.integrations.edit.regenerate-password-btn')'"
                                                @click="confirmRegeneratePassword"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                    <path d="M23 4v6h-6"></path>
                                                    <path d="M1 20v-6h6"></path>
                                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="oauth_client_id" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    @lang('admin::app.configuration.integrations.edit.password-forgot-note')
                                </p>
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
                        api_username: @js(session('api_credentials')['username'] ?? $username),
                        api_password: @js(session('api_credentials')['password'] ?? ''),
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
                        formData.append('name', this.name);
                        formData.append('apiId', this.apiId);
                        this.$axios.post("{{route('admin.configuration.integrations.generate_key')}}", formData)
                            .then((response) => {
                                this.client_id = response.data.client_id;
                                this.secret_key = response.data.secret_key;
                                this.oauth_client_id = response.data.oauth_client_id;
                                this.api_username = response.data.username;
                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('admin::app.configuration.integrations.generate-key-success')" });

                            })
                            .catch(error => {

                            });

                    },

                    copy(value) {
                        navigator.clipboard.writeText(value).then(() => {
                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('admin::app.configuration.integrations.edit.copied')" });
                        });
                    },

                    confirmRegeneratePassword() {
                        this.$emitter.emit('open-confirm-modal', {
                            agree: () => this.regeneratePassword(),
                        });
                    },

                    regeneratePassword() {
                        let formData = new FormData();
                        formData.append('apiId', this.apiId);
                        this.$axios.post("{{ route('admin.configuration.integrations.re_generate_password') }}", formData)
                            .then((response) => {
                                this.api_username = response.data.username;
                                this.api_password = response.data.password;
                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('admin::app.configuration.integrations.edit.regenerate-password-success')" });
                            })
                            .catch(error => {

                            });
                    },

                    reGenerateSecretKey() {
                        let formData = new FormData();
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
