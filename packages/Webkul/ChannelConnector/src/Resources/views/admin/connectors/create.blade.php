<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.connectors.create.title')
    </x-slot>

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

                        <x-admin::form.control-group.control
                            type="select"
                            name="channel_type"
                            :value="old('channel_type')"
                            rules="required"
                            :label="trans('channel_connector::app.connectors.fields.channel-type')"
                        >
                            <option value="">---</option>
                            <option value="shopify">@lang('channel_connector::app.connectors.channel-types.shopify')</option>
                            <option value="salla">@lang('channel_connector::app.connectors.channel-types.salla')</option>
                            <option value="easy_orders">@lang('channel_connector::app.connectors.channel-types.easy_orders')</option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="channel_type" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('channel_connector.connectors.create.card.general.after') !!}

                {!! view_render_event('channel_connector.connectors.create.card.settings.before') !!}

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.connectors.fields.conflict-strategy')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.conflict-strategy')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="settings[conflict_strategy]"
                            :value="old('settings.conflict_strategy', 'always_ask')"
                            :label="trans('channel_connector::app.connectors.fields.conflict-strategy')"
                        >
                            <option value="always_ask" {{ old('settings.conflict_strategy', 'always_ask') === 'always_ask' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.conflict-strategies.always_ask')
                            </option>
                            <option value="pim_always_wins" {{ old('settings.conflict_strategy') === 'pim_always_wins' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.conflict-strategies.pim_always_wins')
                            </option>
                            <option value="channel_always_wins" {{ old('settings.conflict_strategy') === 'channel_always_wins' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.conflict-strategies.channel_always_wins')
                            </option>
                        </x-admin::form.control-group.control>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('channel_connector::app.connectors.conflict-strategy-help')
                        </p>
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('channel_connector.connectors.create.card.settings.after') !!}

                {!! view_render_event('channel_connector.connectors.create.card.credentials.before') !!}

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.connectors.fields.credentials')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.shop-url')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="credentials[shop_url]"
                            :value="old('credentials.shop_url')"
                            :placeholder="trans('channel_connector::app.connectors.fields.shop-url')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.access-token')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="credentials[access_token]"
                            :placeholder="trans('channel_connector::app.connectors.fields.access-token')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.api-key')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="credentials[api_key]"
                            :placeholder="trans('channel_connector::app.connectors.fields.api-key')"
                        />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('channel_connector.connectors.create.card.credentials.after') !!}

                {!! view_render_event('channel_connector.connectors.create.card.webhook-settings.before') !!}

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.webhooks.index.title')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('channel_connector::app.connectors.fields.inbound-strategy')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="settings[inbound_strategy]"
                            :value="old('settings.inbound_strategy', 'flag_for_review')"
                            :label="trans('channel_connector::app.connectors.fields.inbound-strategy')"
                        >
                            <option value="auto_update" {{ old('settings.inbound_strategy') === 'auto_update' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.inbound-strategies.auto_update')
                            </option>
                            <option value="flag_for_review" {{ old('settings.inbound_strategy', 'flag_for_review') === 'flag_for_review' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.inbound-strategies.flag_for_review')
                            </option>
                            <option value="ignore" {{ old('settings.inbound_strategy') === 'ignore' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.inbound-strategies.ignore')
                            </option>
                        </x-admin::form.control-group.control>

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
</x-admin::layouts>
