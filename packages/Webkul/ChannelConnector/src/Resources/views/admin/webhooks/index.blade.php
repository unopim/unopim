<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.webhooks.index.title') - {{ $connector->name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.webhooks.index.title') - {{ $connector->name }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.channel_connector.connectors.edit', $connector->code) }}"
                class="transparent-button"
            >
                @lang('channel_connector::app.general.back')
            </a>
        </div>
    </div>

    <x-admin::form
        :action="route('admin.channel_connector.webhooks.manage', $connector->code)"
        method="POST"
    >
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 overflow-auto max-xl:flex-auto">

                {{-- Webhook URL --}}
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.webhooks.fields.webhook-url')
                    </p>

                    @php
                        $settings = $connector->settings ?? [];
                        $webhookToken = $settings['webhook_token'] ?? null;
                        $currentEvents = $settings['webhook_events'] ?? [];
                        $currentStrategy = $settings['inbound_strategy'] ?? 'flag_for_review';
                    @endphp

                    @if($webhookToken)
                        <div class="flex items-center gap-2">
                            <code
                                id="webhook-url"
                                class="flex-1 rounded bg-gray-100 px-3 py-2 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200"
                            >{{ route('channel_connector.webhooks.receive', $webhookToken) }}</code>

                            <button
                                type="button"
                                onclick="copyWebhookUrl()"
                                class="secondary-button flex items-center gap-1"
                            >
                                <span id="copy-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                @lang('channel_connector::app.webhooks.copy-url')
                            </button>
                        </div>

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            @lang('channel_connector::app.webhooks.webhook-url-info')
                        </p>
                    @else
                        <div class="rounded bg-yellow-50 p-3 dark:bg-yellow-900/20">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                @lang('channel_connector::app.webhooks.no-token')
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Event Subscriptions --}}
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.webhooks.event-subscriptions')
                    </p>

                    <div class="flex flex-col gap-3">
                        <label class="flex items-center gap-3 rounded border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <input
                                type="checkbox"
                                name="events[]"
                                value="product.created"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                {{ in_array('product.created', $currentEvents) ? 'checked' : '' }}
                            />
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    @lang('channel_connector::app.webhooks.events.product-created')
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    product.created
                                </p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 rounded border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <input
                                type="checkbox"
                                name="events[]"
                                value="product.updated"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                {{ in_array('product.updated', $currentEvents) ? 'checked' : '' }}
                            />
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    @lang('channel_connector::app.webhooks.events.product-updated')
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    product.updated
                                </p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 rounded border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <input
                                type="checkbox"
                                name="events[]"
                                value="product.deleted"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                                {{ in_array('product.deleted', $currentEvents) ? 'checked' : '' }}
                            />
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    @lang('channel_connector::app.webhooks.events.product-deleted')
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    product.deleted
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Inbound Strategy --}}
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('channel_connector::app.webhooks.fields.inbound-strategy')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.control
                            type="select"
                            name="inbound_strategy"
                            :value="$currentStrategy"
                            :label="trans('channel_connector::app.webhooks.fields.inbound-strategy')"
                        >
                            <option value="auto_update" {{ $currentStrategy === 'auto_update' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.inbound-strategies.auto_update')
                            </option>
                            <option value="flag_for_review" {{ $currentStrategy === 'flag_for_review' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.inbound-strategies.flag_for_review')
                            </option>
                            <option value="ignore" {{ $currentStrategy === 'ignore' ? 'selected' : '' }}>
                                @lang('channel_connector::app.connectors.inbound-strategies.ignore')
                            </option>
                        </x-admin::form.control-group.control>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('channel_connector::app.webhooks.inbound-strategy-info')
                        </p>
                    </x-admin::form.control-group>

                    {{-- Strategy status badge --}}
                    <div class="mt-3">
                        @if($currentStrategy === 'auto_update')
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                @lang('channel_connector::app.connectors.inbound-strategies.auto_update')
                            </span>
                        @elseif($currentStrategy === 'flag_for_review')
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                @lang('channel_connector::app.connectors.inbound-strategies.flag_for_review')
                            </span>
                        @elseif($currentStrategy === 'ignore')
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                @lang('channel_connector::app.connectors.inbound-strategies.ignore')
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Save Button --}}
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('channel_connector::app.webhooks.save-settings')
                    </button>
                </div>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script type="module">
            window.copyWebhookUrl = function () {
                const urlElement = document.getElementById('webhook-url');
                const url = urlElement.textContent.trim();

                navigator.clipboard.writeText(url).then(() => {
                    const copyIcon = document.getElementById('copy-icon');
                    const originalHtml = copyIcon.innerHTML;

                    copyIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';

                    setTimeout(() => {
                        copyIcon.innerHTML = originalHtml;
                    }, 2000);
                });
            };
        </script>
    @endPushOnce
</x-admin::layouts>
