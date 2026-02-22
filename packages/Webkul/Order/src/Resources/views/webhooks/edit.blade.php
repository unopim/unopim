<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.webhooks.edit.title')
    </x-slot>

    {!! view_render_event('unopim.order.webhooks.edit.before', ['webhook' => $webhook]) !!}

    <x-admin::form
        :action="route('admin.order.webhooks.update', $webhook->id)"
        method="PUT"
    >
        {!! view_render_event('unopim.order.webhooks.edit.form.before', ['webhook' => $webhook]) !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('order::app.admin.webhooks.edit.page-title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Delete Button -->
                @if (bouncer()->hasPermission('order.webhooks.delete'))
                    <form
                        action="{{ route('admin.order.webhooks.delete', $webhook->id) }}"
                        method="POST"
                        onsubmit="return confirm('@lang('order::app.admin.webhooks.edit.delete-confirm')');"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="secondary-button">
                            @lang('order::app.admin.webhooks.edit.delete')
                        </button>
                    </form>
                @endif

                <!-- Cancel Button -->
                <a
                    href="{{ route('admin.order.webhooks.index') }}"
                    class="transparent-button"
                >
                    @lang('order::app.admin.webhooks.edit.cancel')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('order::app.admin.webhooks.edit.save-btn')
                </button>
            </div>
        </div>

        <!-- Body Content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Column -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- General Information -->
                {!! view_render_event('unopim.order.webhooks.edit.card.general.before', ['webhook' => $webhook]) !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.webhooks.edit.general')
                    </p>

                    <!-- Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.webhooks.edit.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="name"
                            name="name"
                            rules="required"
                            :value="old('name', $webhook->name)"
                            :label="trans('order::app.admin.webhooks.edit.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Channel (Read-only) -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.webhooks.edit.channel')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            :value="$webhook->channel->name"
                            readonly
                        />

                        <x-admin::form.control-group.hint>
                            @lang('order::app.admin.webhooks.edit.channel-readonly-hint')
                        </x-admin::form.control-group.hint>
                    </x-admin::form.control-group>

                    <!-- Event Types -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.webhooks.edit.event-types')
                        </x-admin::form.control-group.label>

                        @php
                            $eventOptions = json_encode([
                                ['value' => 'order.created', 'label' => trans('order::app.admin.webhooks.edit.event-order-created')],
                                ['value' => 'order.updated', 'label' => trans('order::app.admin.webhooks.edit.event-order-updated')],
                                ['value' => 'order.cancelled', 'label' => trans('order::app.admin.webhooks.edit.event-order-cancelled')],
                                ['value' => 'order.completed', 'label' => trans('order::app.admin.webhooks.edit.event-order-completed')],
                                ['value' => 'order.shipped', 'label' => trans('order::app.admin.webhooks.edit.event-order-shipped')],
                                ['value' => 'order.delivered', 'label' => trans('order::app.admin.webhooks.edit.event-order-delivered')],
                                ['value' => 'order.refunded', 'label' => trans('order::app.admin.webhooks.edit.event-order-refunded')],
                            ]);

                            $selectedEvents = old('event_types', $webhook->event_types ?? []);
                            if (is_array($selectedEvents)) {
                                $selectedEvents = json_encode($selectedEvents);
                            }
                        @endphp

                        <x-admin::form.control-group.control
                            type="multiselect"
                            id="event_types"
                            name="event_types"
                            rules="required"
                            :options="$eventOptions"
                            :value="$selectedEvents"
                            :label="trans('order::app.admin.webhooks.edit.event-types')"
                            track-by="value"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="event_types" />
                    </x-admin::form.control-group>

                    <!-- Is Active -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.webhooks.edit.is-active')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="switch"
                            id="is_active"
                            name="is_active"
                            value="1"
                            :checked="old('is_active', $webhook->is_active)"
                            :label="trans('order::app.admin.webhooks.edit.is-active')"
                        />

                        <x-admin::form.control-group.error control-name="is_active" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.order.webhooks.edit.card.general.after', ['webhook' => $webhook]) !!}

                <!-- Statistics -->
                @if($webhook->last_triggered_at || $webhook->total_deliveries > 0)
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.webhooks.edit.statistics')
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        @if($webhook->total_deliveries > 0)
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('order::app.admin.webhooks.edit.total-deliveries')
                            </p>
                            <p class="text-base text-gray-800 dark:text-white font-medium">
                                {{ number_format($webhook->total_deliveries) }}
                            </p>
                        </div>
                        @endif

                        @if($webhook->last_triggered_at)
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('order::app.admin.webhooks.edit.last-triggered')
                            </p>
                            <p class="text-base text-gray-800 dark:text-white font-medium">
                                {{ $webhook->last_triggered_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

                <!-- Webhook Endpoint -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.webhooks.edit.endpoint-url')
                    </p>
                    <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                        <code class="text-xs text-gray-600 dark:text-gray-300 break-all">
                            {{ route('order.webhooks.receive', ['channel' => $webhook->channel->code]) }}
                        </code>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        @lang('order::app.admin.webhooks.edit.endpoint-hint')
                    </p>
                </div>

                <!-- Test Webhook -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.webhooks.edit.test-webhook')
                    </p>
                    <form
                        action="{{ route('admin.order.webhooks.test', $webhook->id) }}"
                        method="POST"
                    >
                        @csrf
                        <button type="submit" class="secondary-button w-full">
                            @lang('order::app.admin.webhooks.edit.send-test')
                        </button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        @lang('order::app.admin.webhooks.edit.test-hint')
                    </p>
                </div>
            </div>
        </div>

        {!! view_render_event('unopim.order.webhooks.edit.form.after', ['webhook' => $webhook]) !!}
    </x-admin::form>

    {!! view_render_event('unopim.order.webhooks.edit.after', ['webhook' => $webhook]) !!}

</x-admin::layouts>
