@php
    $selectedEvents = old('events') ?? ($webhook->events ?? []);
    $selectedEvents = is_array($selectedEvents) ? json_encode($selectedEvents) : $selectedEvents;

    $existingHeaders = old('headers') ?? collect($webhook->headers ?? [])
        ->map(fn ($value, $key) => ['key' => $key, 'value' => $value])
        ->values()
        ->all();
@endphp

<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('webhook::app.webhooks.form.general')
            </p>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('webhook::app.webhooks.form.name')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="name"
                    rules="required"
                    :value="old('name') ?? ($webhook->name ?? '')"
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
                    id="webhook_url"
                    name="url"
                    rules="required"
                    :value="old('url') ?? ($webhook->url ?? '')"
                    :label="trans('webhook::app.webhooks.form.url')"
                    placeholder="https://"
                />

                <x-admin::form.control-group.error control-name="url" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('webhook::app.webhooks.form.events')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="multiselect"
                    name="events"
                    rules="required"
                    :options="json_encode($eventOptions)"
                    :value="$selectedEvents"
                    :label="trans('webhook::app.webhooks.form.events')"
                    :placeholder="trans('webhook::app.webhooks.form.select-events')"
                    track-by="id"
                    label-by="label"
                />

                <x-admin::form.control-group.error control-name="events" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('webhook::app.webhooks.form.secret')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="password"
                    name="secret"
                    :value="''"
                    :label="trans('webhook::app.webhooks.form.secret')"
                    :placeholder="isset($webhook) && $webhook->secret ? trans('webhook::app.webhooks.form.secret-set') : ''"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('webhook::app.webhooks.form.secret-hint')
                </p>
            </x-admin::form.control-group>
        </div>

        <v-webhook-headers :existing="{{ json_encode($existingHeaders) }}"></v-webhook-headers>
    </div>

    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('webhook::app.webhooks.form.settings')
            </p>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>
                    @lang('webhook::app.webhooks.form.active')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="switch"
                    name="is_active"
                    value="1"
                    :label="trans('webhook::app.webhooks.form.active')"
                    :checked="old('is_active') ?? ($webhook->is_active ?? true)"
                />
            </x-admin::form.control-group>
        </div>

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-2 text-base text-gray-800 dark:text-white font-semibold">
                @lang('webhook::app.webhooks.form.test')
            </p>

            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                @lang('webhook::app.webhooks.form.test-hint')
            </p>

            <v-webhook-test></v-webhook-test>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-webhook-headers-template">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex justify-between items-center mb-4">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    @lang('webhook::app.webhooks.form.headers')
                </p>

                <button
                    type="button"
                    class="secondary-button"
                    @click="addRow"
                >
                    @lang('webhook::app.webhooks.form.add-header')
                </button>
            </div>

            <p
                v-if="! rows.length"
                class="text-xs text-gray-500 dark:text-gray-400"
            >
                @lang('webhook::app.webhooks.form.no-headers')
            </p>

            <div
                v-for="(row, index) in rows"
                :key="index"
                class="flex gap-2.5 items-start mb-2.5"
            >
                <x-admin::form.control-group class="!mb-0 flex-1">
                    <x-admin::form.control-group.control
                        type="text"
                        ::name="`headers[${index}][key]`"
                        ::value="row.key"
                        @input="row.key = $event.target.value"
                        :placeholder="trans('webhook::app.webhooks.form.header-key')"
                    />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="!mb-0 flex-1">
                    <x-admin::form.control-group.control
                        type="text"
                        ::name="`headers[${index}][value]`"
                        ::value="row.value"
                        @input="row.value = $event.target.value"
                        :placeholder="trans('webhook::app.webhooks.form.header-value')"
                    />
                </x-admin::form.control-group>

                <span
                    class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800"
                    @click="removeRow(index)"
                ></span>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="v-webhook-test-template">
        <button
            type="button"
            class="secondary-button w-full flex items-center justify-center gap-2"
            :disabled="isTesting"
            @click="runTest"
        >
            <span v-show="isTesting" class="icon-spinner animate-spin"></span>
            <span>@lang('webhook::app.webhooks.form.test-btn')</span>
        </button>
    </script>

    <script type="module">
        app.component('v-webhook-headers', {
            template: '#v-webhook-headers-template',

            props: {
                existing: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    rows: Array.isArray(this.existing) ? [...this.existing] : [],
                };
            },

            methods: {
                addRow() {
                    this.rows.push({ key: '', value: '' });
                },

                removeRow(index) {
                    this.rows.splice(index, 1);
                },
            },
        });

        app.component('v-webhook-test', {
            template: '#v-webhook-test-template',

            data() {
                return {
                    isTesting: false,
                };
            },

            methods: {
                runTest() {
                    const url = document.getElementById('webhook_url')?.value ?? '';

                    if (! url) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('webhook::app.webhooks.form.test-no-url')",
                        });

                        return;
                    }

                    this.isTesting = true;

                    this.$axios.post("{{ route('webhook.test') }}", { url })
                        .then((response) => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message ?? "@lang('webhook::app.webhooks.form.test-failed')",
                            });
                        })
                        .finally(() => this.isTesting = false);
                },
            },
        });
    </script>
@endPushOnce
