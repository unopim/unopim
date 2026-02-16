<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.connectors.create.title')
    </x-slot>

    <v-create-connector></v-create-connector>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-connector-template"
        >
            <x-admin::form
                :action="route('admin.channel_connector.connectors.store')"
                method="POST"
                enctype="multipart/form-data"
            >
                <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.connectors.create.title')
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

                        {!! view_render_event('channel_connector.connectors.create.card.general.before') !!}

                        <!-- General Info -->
                        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.connectors.fields.code')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('channel_connector::app.connectors.fields.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    :value="old('code')"
                                    rules="required"
                                    :label="trans('channel_connector::app.connectors.fields.code')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.code')"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('channel_connector::app.connectors.fields.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="name"
                                    :value="old('name')"
                                    rules="required"
                                    :label="trans('channel_connector::app.connectors.fields.name')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.name')"
                                />

                                <x-admin::form.control-group.error control-name="name" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('channel_connector::app.connectors.fields.channel-type')
                                </x-admin::form.control-group.label>

                                @php
                                    $channelTypes = [
                                        ['id' => 'shopify',     'label' => trans('channel_connector::app.connectors.channel-types.shopify')],
                                        ['id' => 'salla',       'label' => trans('channel_connector::app.connectors.channel-types.salla')],
                                        ['id' => 'amazon',      'label' => trans('channel_connector::app.connectors.channel-types.amazon')],
                                        ['id' => 'woocommerce', 'label' => trans('channel_connector::app.connectors.channel-types.woocommerce')],
                                        ['id' => 'ebay',        'label' => trans('channel_connector::app.connectors.channel-types.ebay')],
                                        ['id' => 'magento2',    'label' => trans('channel_connector::app.connectors.channel-types.magento2')],
                                        ['id' => 'noon',        'label' => trans('channel_connector::app.connectors.channel-types.noon')],
                                        ['id' => 'easyorders',  'label' => trans('channel_connector::app.connectors.channel-types.easyorders')],
                                    ];

                                    $channelTypesJson = json_encode($channelTypes);
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="channel_type"
                                    class="cursor-pointer"
                                    name="channel_type"
                                    rules="required"
                                    :value="old('channel_type')"
                                    v-model="channelType"
                                    :label="trans('channel_connector::app.connectors.fields.channel-type')"
                                    :options="$channelTypesJson"
                                    track-by="id"
                                    label-by="label"
                                >
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="channel_type" />
                            </x-admin::form.control-group>
                        </div>

                        {!! view_render_event('channel_connector.connectors.create.card.general.after') !!}

                        {!! view_render_event('channel_connector.connectors.create.card.settings.before') !!}

                        <!-- Conflict Strategy -->
                        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.connectors.fields.conflict-strategy')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('channel_connector::app.connectors.fields.conflict-strategy')
                                </x-admin::form.control-group.label>

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
                                    :value="old('settings.conflict_strategy', 'always_ask')"
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

                        {!! view_render_event('channel_connector.connectors.create.card.settings.after') !!}

                        {!! view_render_event('channel_connector.connectors.create.card.credentials.before') !!}

                        <!-- Credentials: Shopify -->
                        <div
                            v-if="selectedChannelType === 'shopify'"
                            class="box-shadow rounded bg-white p-4 dark:bg-gray-900"
                        >
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.connectors.fields.credentials')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('channel_connector::app.connectors.fields.shop-url')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="credentials[shop_url]"
                                    :value="old('credentials.shop_url')"
                                    rules="required"
                                    :label="trans('channel_connector::app.connectors.fields.shop-url')"
                                    placeholder="my-store.myshopify.com"
                                />

                                <x-admin::form.control-group.error control-name="credentials[shop_url]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('channel_connector::app.connectors.fields.access-token')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="credentials[access_token]"
                                    rules="required"
                                    :label="trans('channel_connector::app.connectors.fields.access-token')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.access-token')"
                                />

                                <x-admin::form.control-group.error control-name="credentials[access_token]" />
                            </x-admin::form.control-group>
                        </div>

                        <!-- Credentials: OAuth2 (all other adapters) -->
                        <div
                            v-else-if="selectedChannelType"
                            class="box-shadow rounded bg-white p-4 dark:bg-gray-900"
                        >
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.connectors.fields.credentials')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('channel_connector::app.connectors.fields.access-token')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="credentials[access_token]"
                                    rules="required"
                                    :label="trans('channel_connector::app.connectors.fields.access-token')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.access-token')"
                                />

                                <x-admin::form.control-group.error control-name="credentials[access_token]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('channel_connector::app.connectors.fields.client-id')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="credentials[client_id]"
                                    :value="old('credentials.client_id')"
                                    :label="trans('channel_connector::app.connectors.fields.client-id')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.client-id')"
                                />

                                <x-admin::form.control-group.error control-name="credentials[client_id]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('channel_connector::app.connectors.fields.client-secret')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="credentials[client_secret]"
                                    :label="trans('channel_connector::app.connectors.fields.client-secret')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.client-secret')"
                                />

                                <x-admin::form.control-group.error control-name="credentials[client_secret]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('channel_connector::app.connectors.fields.refresh-token')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    name="credentials[refresh_token]"
                                    :label="trans('channel_connector::app.connectors.fields.refresh-token')"
                                    :placeholder="trans('channel_connector::app.connectors.fields.refresh-token')"
                                />

                                <x-admin::form.control-group.error control-name="credentials[refresh_token]" />
                            </x-admin::form.control-group>
                        </div>

                        <!-- Credentials: No channel type selected -->
                        <div
                            v-else
                            class="box-shadow rounded bg-white p-4 dark:bg-gray-900"
                        >
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.connectors.fields.credentials')
                            </p>

                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @lang('channel_connector::app.connectors.fields.select-channel-type')
                            </p>
                        </div>

                        {!! view_render_event('channel_connector.connectors.create.card.credentials.after') !!}

                        {!! view_render_event('channel_connector.connectors.create.card.webhook-settings.before') !!}

                        <!-- Webhook Settings -->
                        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.webhooks.index.title')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('channel_connector::app.connectors.fields.inbound-strategy')
                                </x-admin::form.control-group.label>

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
                                    :value="old('settings.inbound_strategy', 'flag_for_review')"
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
                                            {{ in_array('product.created', old('settings.webhook_events', [])) ? 'checked' : '' }}
                                        />
                                        @lang('channel_connector::app.webhooks.events.product-created')
                                    </label>

                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input
                                            type="checkbox"
                                            name="settings[webhook_events][]"
                                            value="product.updated"
                                            class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-800"
                                            {{ in_array('product.updated', old('settings.webhook_events', [])) ? 'checked' : '' }}
                                        />
                                        @lang('channel_connector::app.webhooks.events.product-updated')
                                    </label>

                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input
                                            type="checkbox"
                                            name="settings[webhook_events][]"
                                            value="product.deleted"
                                            class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-800"
                                            {{ in_array('product.deleted', old('settings.webhook_events', [])) ? 'checked' : '' }}
                                        />
                                        @lang('channel_connector::app.webhooks.events.product-deleted')
                                    </label>
                                </div>
                            </div>
                        </div>

                        {!! view_render_event('channel_connector.connectors.create.card.webhook-settings.after') !!}
                    </div>
                </div>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-create-connector', {
                template: '#v-create-connector-template',

                data() {
                    return {
                        channelType: @json(old('channel_type')) ?? '',
                        selectedChannelType: @json(old('channel_type')) ?? '',
                    };
                },

                watch: {
                    channelType(value) {
                        this.selectedChannelType = this.parseValue(value)?.id;
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
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
