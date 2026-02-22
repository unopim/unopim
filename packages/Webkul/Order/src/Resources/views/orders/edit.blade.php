<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.orders.edit.title', ['order_number' => $order->channel_order_id])
    </x-slot>

    {!! view_render_event('unopim.order.orders.edit.before', ['order' => $order]) !!}

    <x-admin::form
        :action="route('admin.order.orders.update', $order->id)"
        method="PUT"
    >
        {!! view_render_event('unopim.order.orders.edit.form.before', ['order' => $order]) !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('order::app.admin.orders.edit.page-title', ['order_number' => $order->channel_order_id])
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Cancel Button -->
                <a
                    href="{{ route('admin.order.orders.show', $order->id) }}"
                    class="transparent-button"
                >
                    @lang('order::app.admin.orders.edit.cancel')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('order::app.admin.orders.edit.save-btn')
                </button>
            </div>
        </div>

        <!-- Body Content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Column -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- Editable Fields -->
                {!! view_render_event('unopim.order.orders.edit.card.general.before', ['order' => $order]) !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.orders.edit.general')
                    </p>

                    <!-- Status -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.orders.edit.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="status"
                            name="status"
                            rules="required"
                            :value="old('status', $order->status->value)"
                            :label="trans('order::app.admin.orders.edit.status')"
                        >
                            <option value="pending" {{ $order->status->value === 'pending' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-pending')
                            </option>
                            <option value="processing" {{ $order->status->value === 'processing' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-processing')
                            </option>
                            <option value="shipped" {{ $order->status->value === 'shipped' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-shipped')
                            </option>
                            <option value="delivered" {{ $order->status->value === 'delivered' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-delivered')
                            </option>
                            <option value="completed" {{ $order->status->value === 'completed' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-completed')
                            </option>
                            <option value="cancelled" {{ $order->status->value === 'cancelled' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-cancelled')
                            </option>
                            <option value="refunded" {{ $order->status->value === 'refunded' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-refunded')
                            </option>
                            <option value="on_hold" {{ $order->status->value === 'on_hold' ? 'selected' : '' }}>
                                @lang('order::app.admin.orders.edit.status-on-hold')
                            </option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>

                    <!-- Tracking Number -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.orders.edit.tracking-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="tracking_number"
                            name="tracking_number"
                            :value="old('tracking_number', $order->tracking_number)"
                            :label="trans('order::app.admin.orders.edit.tracking-number')"
                            :placeholder="trans('order::app.admin.orders.edit.tracking-placeholder')"
                        />

                        <x-admin::form.control-group.error control-name="tracking_number" />
                    </x-admin::form.control-group>

                    <!-- Internal Notes -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.orders.edit.internal-notes')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="internal_notes"
                            name="internal_notes"
                            :value="old('internal_notes', $order->internal_notes)"
                            :label="trans('order::app.admin.orders.edit.internal-notes')"
                            :placeholder="trans('order::app.admin.orders.edit.notes-placeholder')"
                            rows="4"
                        />

                        <x-admin::form.control-group.error control-name="internal_notes" />

                        <x-admin::form.control-group.hint>
                            @lang('order::app.admin.orders.edit.notes-hint')
                        </x-admin::form.control-group.hint>
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.order.orders.edit.card.general.after', ['order' => $order]) !!}

                <!-- Read-Only Order Summary -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.orders.edit.order-summary')
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('order::app.admin.orders.edit.order-id')
                            </p>
                            <p class="text-base text-gray-800 dark:text-white">
                                {{ $order->channel_order_id }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('order::app.admin.orders.edit.channel')
                            </p>
                            <p class="text-base text-gray-800 dark:text-white">
                                {{ $order->channel->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('order::app.admin.orders.edit.order-date')
                            </p>
                            <p class="text-base text-gray-800 dark:text-white">
                                {{ $order->order_date->format('M d, Y H:i') }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('order::app.admin.orders.edit.total')
                            </p>
                            <p class="text-base text-gray-800 dark:text-white">
                                {{ $order->formatted_total_amount }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Warning -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900 rounded box-shadow border border-yellow-200 dark:border-yellow-700">
                    <p class="mb-2 text-base text-yellow-800 dark:text-yellow-200 font-semibold">
                        @lang('order::app.admin.orders.edit.edit-warning-title')
                    </p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        @lang('order::app.admin.orders.edit.edit-warning-message')
                    </p>
                </div>
            </div>
        </div>

        {!! view_render_event('unopim.order.orders.edit.form.after', ['order' => $order]) !!}
    </x-admin::form>

    {!! view_render_event('unopim.order.orders.edit.after', ['order' => $order]) !!}

</x-admin::layouts>
