<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.webhooks.create.title')
    </x-slot>

    {!! view_render_event('unopim.order.webhooks.create.before') !!}

    <x-admin::form :action="route('admin.order.webhooks.store')">

        {!! view_render_event('unopim.order.webhooks.create.form.before') !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('order::app.admin.webhooks.create.page-title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Cancel Button -->
                <a
                    href="{{ route('admin.order.webhooks.index') }}"
                    class="transparent-button"
                >
                    @lang('order::app.admin.webhooks.create.cancel')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('order::app.admin.webhooks.create.save-btn')
                </button>
            </div>
        </div>

        <!-- Body Content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Column -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- General Information -->
                {!! view_render_event('unopim.order.webhooks.create.card.general.before') !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.webhooks.create.general')
                    </p>

                    <!-- Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.webhooks.create.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="name"
                            name="name"
                            rules="required"
                            :value="old('name')"
                            :label="trans('order::app.admin.webhooks.create.name')"
                            :placeholder="trans('order::app.admin.webhooks.create.name-placeholder')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Channel -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.webhooks.create.channel')
                        </x-admin::form.control-group.label>

                        @php
                            $channels = app('Webkul\Channel\Repositories\ChannelRepository')->all();
                            $channelOptions = json_encode($channels->map(function($channel) {
                                return [
                                    'id' => $channel->id,
                                    'name' => $channel->name,
                                ];
                            })->toArray());
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="channel_id"
                            name="channel_id"
                            rules="required"
                            :options="$channelOptions"
                            :value="old('channel_id')"
                            :label="trans('order::app.admin.webhooks.create.channel')"
                            :placeholder="trans('order::app.admin.webhooks.create.select-channel')"
                            track-by="id"
                            label-by="name"
                        />

                        <x-admin::form.control-group.error control-name="channel_id" />
                    </x-admin::form.control-group>

                    <!-- Event Types -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.webhooks.create.event-types')
                        </x-admin::form.control-group.label>

                        @php
                            $eventOptions = json_encode([
                                ['value' => 'order.created', 'label' => trans('order::app.admin.webhooks.create.event-order-created')],
                                ['value' => 'order.updated', 'label' => trans('order::app.admin.webhooks.create.event-order-updated')],
                                ['value' => 'order.cancelled', 'label' => trans('order::app.admin.webhooks.create.event-order-cancelled')],
                                ['value' => 'order.completed', 'label' => trans('order::app.admin.webhooks.create.event-order-completed')],
                                ['value' => 'order.shipped', 'label' => trans('order::app.admin.webhooks.create.event-order-shipped')],
                                ['value' => 'order.delivered', 'label' => trans('order::app.admin.webhooks.create.event-order-delivered')],
                                ['value' => 'order.refunded', 'label' => trans('order::app.admin.webhooks.create.event-order-refunded')],
                            ]);

                            $oldEventTypes = old('event_types');
                            if (is_array($oldEventTypes)) {
                                $oldEventTypes = json_encode($oldEventTypes);
                            }
                        @endphp

                        <x-admin::form.control-group.control
                            type="multiselect"
                            id="event_types"
                            name="event_types"
                            rules="required"
                            :options="$eventOptions"
                            :value="$oldEventTypes"
                            :label="trans('order::app.admin.webhooks.create.event-types')"
                            :placeholder="trans('order::app.admin.webhooks.create.select-events')"
                            track-by="value"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="event_types" />

                        <x-admin::form.control-group.hint>
                            @lang('order::app.admin.webhooks.create.event-hint')
                        </x-admin::form.control-group.hint>
                    </x-admin::form.control-group>

                    <!-- Is Active -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.webhooks.create.is-active')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            id="is_active"
                            name="is_active"
                            value="1"
                            :checked="old('is_active', true)"
                            :label="trans('order::app.admin.webhooks.create.is-active')"
                        />

                        <x-admin::form.control-group.error control-name="is_active" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.order.webhooks.create.card.general.after') !!}
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

                <!-- Webhook Endpoint Info -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded box-shadow border border-blue-200 dark:border-blue-700">
                    <p class="mb-2 text-base text-blue-800 dark:text-blue-200 font-semibold">
                        @lang('order::app.admin.webhooks.create.endpoint-info-title')
                    </p>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                        @lang('order::app.admin.webhooks.create.endpoint-info-message')
                    </p>
                    <div class="p-2 bg-white dark:bg-blue-800 rounded border border-blue-300 dark:border-blue-600">
                        <p class="text-xs text-gray-600 dark:text-gray-300 mb-1">
                            @lang('order::app.admin.webhooks.create.endpoint-url-label')
                        </p>
                        <code class="text-xs text-blue-600 dark:text-blue-300 break-all" id="webhookEndpoint">
                            {{ route('order.webhooks.receive', ['channel' => '{channel_code}']) }}
                        </code>
                    </div>
                </div>

                <!-- Webhook Security -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.webhooks.create.security')
                    </p>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 list-disc list-inside">
                        <li>@lang('order::app.admin.webhooks.create.security-1')</li>
                        <li>@lang('order::app.admin.webhooks.create.security-2')</li>
                        <li>@lang('order::app.admin.webhooks.create.security-3')</li>
                    </ul>
                </div>

                <!-- Supported Events Info -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                            @lang('order::app.admin.webhooks.create.supported-events')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                            <div>
                                <strong>order.created</strong>
                                <p class="text-xs">@lang('order::app.admin.webhooks.create.event-desc-created')</p>
                            </div>
                            <div>
                                <strong>order.updated</strong>
                                <p class="text-xs">@lang('order::app.admin.webhooks.create.event-desc-updated')</p>
                            </div>
                            <div>
                                <strong>order.cancelled</strong>
                                <p class="text-xs">@lang('order::app.admin.webhooks.create.event-desc-cancelled')</p>
                            </div>
                            <div>
                                <strong>order.completed</strong>
                                <p class="text-xs">@lang('order::app.admin.webhooks.create.event-desc-completed')</p>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>

        {!! view_render_event('unopim.order.webhooks.create.form.after') !!}

    </x-admin::form>

    {!! view_render_event('unopim.order.webhooks.create.after') !!}

    @push('scripts')
    <script>
        // Update webhook endpoint URL when channel is selected
        document.getElementById('channel_id')?.addEventListener('change', function(e) {
            const channelSelect = e.target;
            const selectedOption = channelSelect.options[channelSelect.selectedIndex];
            const channelCode = selectedOption?.getAttribute('data-code') || '{channel_code}';

            const endpointEl = document.getElementById('webhookEndpoint');
            if (endpointEl) {
                endpointEl.textContent = endpointEl.textContent.replace(/{channel_code}/, channelCode);
            }
        });
    </script>
    @endpush

</x-admin::layouts>
