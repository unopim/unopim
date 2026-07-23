<x-admin::layouts>
    <x-slot:title>
        @lang('webhook::app.webhooks.index.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('webhook::app.webhooks.index.title')">
        <x-slot:actions>
            @if (bouncer()->hasPermission('configuration.webhook.logs'))
                <a
                    href="{{ route('webhook.logs.index') }}"
                    class="transparent-button"
                >
                    @lang('webhook::app.webhooks.index.logs-btn')
                </a>
            @endif

            {!! view_render_event('unopim.webhook.webhooks.create.before') !!}

            @if (bouncer()->hasPermission('configuration.webhook.create'))
                <v-create-webhook-form></v-create-webhook-form>
            @endif

            {!! view_render_event('unopim.webhook.webhooks.create.after') !!}
        </x-slot>
    </x-admin::layouts.page-header>

    <x-admin::datagrid :src="route('webhook.index')" />

    @if (bouncer()->hasPermission('configuration.webhook.create'))
        @pushOnce('scripts')
            <script type="text/x-template" id="v-create-webhook-form-template">
                <div>
                    <button
                        type="button"
                        class="primary-button"
                        @click="$refs.webhookCreateModal.toggle()"
                    >
                        @lang('webhook::app.webhooks.index.create-btn')
                    </button>

                    <x-admin::form
                        v-slot="{ meta, errors, handleSubmit }"
                        as="div"
                    >
                        <form @submit="handleSubmit($event, create)" ref="webhookCreateForm">
                            <x-admin::modal ref="webhookCreateModal">
                                <x-slot:header>
                                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                                        @lang('webhook::app.webhooks.create.title')
                                    </p>
                                </x-slot>

                                <x-slot:content>
                                    {!! view_render_event('unopim.webhook.webhooks.create_form.controls.before') !!}

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('webhook::app.webhooks.form.name')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="name"
                                            rules="required"
                                            :label="trans('webhook::app.webhooks.form.name')"
                                            :placeholder="trans('webhook::app.webhooks.form.name')"
                                        />

                                        <x-admin::form.control-group.error control-name="name" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('webhook::app.webhooks.form.url')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="url"
                                            rules="required"
                                            :label="trans('webhook::app.webhooks.form.url')"
                                            placeholder="https://"
                                        />

                                        <x-admin::form.control-group.error control-name="url" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('webhook::app.webhooks.form.events')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="multiselect"
                                            name="events"
                                            rules="required"
                                            :options="json_encode($eventOptions)"
                                            :label="trans('webhook::app.webhooks.form.events')"
                                            :placeholder="trans('webhook::app.webhooks.form.select-events')"
                                            track-by="id"
                                            label-by="label"
                                        />

                                        <x-admin::form.control-group.error control-name="events" />
                                    </x-admin::form.control-group>

                                    {!! view_render_event('unopim.webhook.webhooks.create_form.controls.after') !!}
                                </x-slot>

                                <x-slot:footer>
                                    <button
                                        type="submit"
                                        class="primary-button"
                                    >
                                        @lang('webhook::app.webhooks.create.save-btn')
                                    </button>
                                </x-slot>
                            </x-admin::modal>
                        </form>
                    </x-admin::form>
                </div>
            </script>

            <script type="module">
                app.component('v-create-webhook-form', {
                    template: '#v-create-webhook-form-template',

                    methods: {
                        create(params, { setErrors }) {
                            this.$axios.post("{{ route('webhook.store') }}", new FormData(this.$refs.webhookCreateForm))
                                .then((response) => {
                                    this.$navigate(response.data.data.redirect_url);
                                })
                                .catch(error => {
                                    if (error.response.status == 422) {
                                        setErrors(error.response.data.errors);
                                    }
                                });
                        },
                    },
                });
            </script>
        @endPushOnce
    @endif
</x-admin::layouts>
