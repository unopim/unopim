<x-admin::layouts>
    <x-slot:title>
        @lang('shopify::app.shopify.credential.index.title')
    </x-slot>

    <v-credential>
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('shopify::app.shopify.credential.index.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Create User Button -->
                @if (bouncer()->hasPermission('shopify.credentials.create'))
                    <button
                        type="button"
                        class="primary-button"
                    >
                        @lang('shopify::app.shopify.credential.index.create')
                    </button>
                @endif
            </div>
        </div>

        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />
    </v-credential>
    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-credential-template"
        >
            <div class="flex justify-between items-center">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('shopify::app.shopify.credential.index.title')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    <!-- User Create Button -->
                    @if (bouncer()->hasPermission('shopify.credentials.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="$refs.credentialCreateModal.open()"
                        >
                            @lang('shopify::app.shopify.credential.index.create')
                        </button>
                    @endif
                </div>
            </div>
            <!-- Datagrid -->
            <x-admin::datagrid :src="route('shopify.credentials.index')" ref="datagrid" class="mb-8"/>
            <!-- Modal Form -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, create)"
                    ref="credentialCreateForm"
                >
                    <!-- User Create Modal -->
                    <x-admin::modal ref="credentialCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                             <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('shopify::app.shopify.credential.index.create')
                            </p>

                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <!-- Name -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.credential.index.url')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text"
                                    id="shopUrl"
                                    name="shopUrl"
                                    rules="required"
                                    :label="trans('shopify::app.shopify.credential.index.url')"
                                    :placeholder="trans('shopify::app.shopify.credential.index.shopifyurlplaceholder')"
                                />

                                <x-admin::form.control-group.error control-name="shopUrl" />
                            </x-admin::form.control-group>

                            <!-- accesstoken -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.credential.index.accesstoken')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="accessToken"
                                    name="accessToken"
                                    rules="required"
                                    :label="trans('shopify::app.shopify.credential.index.accesstoken')"
                                    :placeholder="trans('shopify::app.shopify.credential.index.accesstoken')"
                                />

                                <x-admin::form.control-group.error control-name="accessToken" />
                            </x-admin::form.control-group>
                            
                            <x-admin::form.control-group class="mb-4">
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.credential.index.apiVersion')
                                </x-admin::form.control-group.label>

                                @php
                                    $apiVersion = json_encode($apiVersion, true);
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="apiVersion"
                                    disabled="disabled"
                                    name="apiVersion"
                                    rules="required"
                                    :label="trans('shopify::app.shopify.credential.index.apiVersion')"
                                    :placeholder="trans('shopify::app.shopify.credential.index.apiVersion')"
                                    :options="$apiVersion"
                                    value="2025-01"
                                    track-by="id"
                                    label-by="name"
                                >
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="apiVersion" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('shopify::app.shopify.credential.index.save')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-credential', {
                template: '#v-credential-template',

                methods: {
                    create(params, { setErrors }) {
                        let formData = new FormData(this.$refs.credentialCreateForm);

                        this.$axios.post("{{ route('shopify.credentials.store') }}", formData)
                            .then((response) => {
                                window.location.href = response.data.redirect_url;
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
