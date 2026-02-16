<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.connectors.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.channel_connector.connectors.update', $connector->code)"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('channel_connector::app.connectors.edit.title') - {{ $connector->name }}
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.channel_connector.connectors.index') }}"
                    class="transparent-button"
                >
                    @lang('channel_connector::app.general.back')
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('channel_connector::app.general.save')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 overflow-auto max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.connectors.fields.code')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('channel_connector::app.connectors.fields.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            :value="old('name', $connector->name)"
                            rules="required"
                            :label="trans('channel_connector::app.connectors.fields.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.status')
                        </x-admin::form.control-group.label>

                        @php
                            $statusOptions = [
                                ['id' => 'connected',    'label' => trans('channel_connector::app.connectors.status.connected')],
                                ['id' => 'disconnected', 'label' => trans('channel_connector::app.connectors.status.disconnected')],
                                ['id' => 'error',        'label' => trans('channel_connector::app.connectors.status.error')],
                            ];

                            $statusOptionsJson = json_encode($statusOptions);
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            name="status"
                            :value="old('status', $connector->status)"
                            :label="trans('channel_connector::app.connectors.fields.status')"
                            :options="$statusOptionsJson"
                            track-by="id"
                            label-by="label"
                        />
                    </x-admin::form.control-group>

                    <div class="mb-4 flex items-center gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            @lang('channel_connector::app.connectors.fields.channel-type'):
                            <strong>{{ trans("channel_connector::app.connectors.channel-types.{$connector->channel_type}") }}</strong>
                        </span>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.connectors.fields.conflict-strategy')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.conflict-strategy')
                        </x-admin::form.control-group.label>

                        @php
                            $currentConflictStrategy = $connector->settings['conflict_strategy'] ?? 'always_ask';
                        @endphp

                        @php
                            $conflictStrategies = [
                                ['id' => 'always_ask',          'label' => trans('channel_connector::app.connectors.conflict-strategies.always_ask')],
                                ['id' => 'pim_always_wins',     'label' => trans('channel_connector::app.connectors.conflict-strategies.pim_always_wins')],
                                ['id' => 'channel_always_wins', 'label' => trans('channel_connector::app.connectors.conflict-strategies.channel_always_wins')],
                            ];

                            $conflictStrategiesJson = json_encode($conflictStrategies);
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            name="settings[conflict_strategy]"
                            :value="old('settings.conflict_strategy', $currentConflictStrategy)"
                            :label="trans('channel_connector::app.connectors.fields.conflict-strategy')"
                            :options="$conflictStrategiesJson"
                            track-by="id"
                            label-by="label"
                        />

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('channel_connector::app.connectors.conflict-strategy-help')
                        </p>
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.connectors.fields.credentials')
                    </p>

                    @if ($connector->channel_type === 'shopify')
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('channel_connector::app.connectors.fields.shop-url')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="credentials[shop_url]"
                                placeholder="my-store.myshopify.com"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('channel_connector::app.connectors.fields.access-token')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="credentials[access_token]"
                                placeholder="••••••••"
                            />
                        </x-admin::form.control-group>
                    @else
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('channel_connector::app.connectors.fields.access-token')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="credentials[access_token]"
                                placeholder="••••••••"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('channel_connector::app.connectors.fields.client-id')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="credentials[client_id]"
                                placeholder="••••••••"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('channel_connector::app.connectors.fields.client-secret')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="credentials[client_secret]"
                                placeholder="••••••••"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('channel_connector::app.connectors.fields.refresh-token')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="credentials[refresh_token]"
                                placeholder="••••••••"
                            />
                        </x-admin::form.control-group>
                    @endif

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @lang('channel_connector::app.connectors.fields.access-token-help')
                    </p>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.webhooks.index.title')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.inbound-strategy')
                        </x-admin::form.control-group.label>

                        @php
                            $currentStrategy = $connector->settings['inbound_strategy'] ?? 'flag_for_review';
                            $currentEvents = $connector->settings['webhook_events'] ?? [];
                        @endphp

                        @php
                            $inboundStrategies = [
                                ['id' => 'auto_update',     'label' => trans('channel_connector::app.connectors.inbound-strategies.auto_update')],
                                ['id' => 'flag_for_review', 'label' => trans('channel_connector::app.connectors.inbound-strategies.flag_for_review')],
                                ['id' => 'ignore',          'label' => trans('channel_connector::app.connectors.inbound-strategies.ignore')],
                            ];

                            $inboundStrategiesJson = json_encode($inboundStrategies);
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            name="settings[inbound_strategy]"
                            :value="old('settings.inbound_strategy', $currentStrategy)"
                            :label="trans('channel_connector::app.connectors.fields.inbound-strategy')"
                            :options="$inboundStrategiesJson"
                            track-by="id"
                            label-by="label"
                        />

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('channel_connector::app.webhooks.inbound-strategy-info')
                        </p>
                    </x-admin::form.control-group>

                    <div class="mt-4">
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            @lang('channel_connector::app.webhooks.event-subscriptions')
                        </p>

                        <div class="flex flex-col gap-2">
                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input
                                    type="checkbox"
                                    name="settings[webhook_events][]"
                                    value="product.created"
                                    class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-800"
                                    {{ in_array('product.created', $currentEvents) ? 'checked' : '' }}
                                />
                                @lang('channel_connector::app.webhooks.events.product-created')
                            </label>

                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input
                                    type="checkbox"
                                    name="settings[webhook_events][]"
                                    value="product.updated"
                                    class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-800"
                                    {{ in_array('product.updated', $currentEvents) ? 'checked' : '' }}
                                />
                                @lang('channel_connector::app.webhooks.events.product-updated')
                            </label>

                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <input
                                    type="checkbox"
                                    name="settings[webhook_events][]"
                                    value="product.deleted"
                                    class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-800"
                                    {{ in_array('product.deleted', $currentEvents) ? 'checked' : '' }}
                                />
                                @lang('channel_connector::app.webhooks.events.product-deleted')
                            </label>
                        </div>
                    </div>

                    @if(! empty($connector->settings['webhook_token']))
                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                @lang('channel_connector::app.webhooks.fields.webhook-url')
                            </p>
                            <div class="mt-1 flex items-center gap-2">
                                <code class="flex-1 rounded bg-gray-100 px-3 py-2 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    {{ route('channel_connector.webhooks.receive', $connector->settings['webhook_token']) }}
                                </code>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.general.test-connection')
                    </p>

                    <v-test-connection
                        connector-code="{{ $connector->code }}"
                        test-url="{{ route('admin.channel_connector.connectors.test', $connector->code) }}"
                    ></v-test-connection>
                </div>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-test-connection-template"
        >
            <div>
                <button
                    type="button"
                    class="secondary-button"
                    @click="testConnection"
                    :disabled="isLoading"
                >
                    <span v-if="isLoading" class="animate-spin">&#8634;</span>
                    <span v-else>@lang('channel_connector::app.general.test-connection')</span>
                </button>

                <div v-if="result" class="mt-3 rounded p-3" :class="result.success ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                    <p :class="result.success ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                        @{{ result.message }}
                    </p>

                    <div v-if="result.success && result.channel_info" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <p v-if="result.channel_info.store_name"><strong>@lang('channel_connector::app.general.store'):</strong> @{{ result.channel_info.store_name }}</p>
                        <p v-if="result.channel_info.product_count"><strong>@lang('channel_connector::app.general.products'):</strong> @{{ result.channel_info.product_count }}</p>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-test-connection', {
                template: '#v-test-connection-template',

                props: ['connectorCode', 'testUrl'],

                data() {
                    return {
                        isLoading: false,
                        result: null,
                    };
                },

                methods: {
                    testConnection() {
                        this.isLoading = true;
                        this.result = null;

                        this.$axios.post(this.testUrl)
                            .then(response => {
                                this.result = response.data;
                            })
                            .catch(error => {
                                this.result = {
                                    success: false,
                                    message: error.response?.data?.message || '@lang('channel_connector::app.connectors.test-failed')',
                                };
                            })
                            .finally(() => {
                                this.isLoading = false;
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
