<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.sync.manual.title')
    </x-slot>

    {!! view_render_event('unopim.order.sync.manual.before') !!}

    <x-admin::form :action="route('admin.order.sync.execute')">

        {!! view_render_event('unopim.order.sync.manual.form.before') !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('order::app.admin.sync.manual.page-title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Cancel Button -->
                <a
                    href="{{ route('admin.order.orders.index') }}"
                    class="transparent-button"
                >
                    @lang('order::app.admin.sync.manual.cancel')
                </a>

                <!-- Sync Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('order::app.admin.sync.manual.sync-btn')
                </button>
            </div>
        </div>

        <!-- Body Content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Column -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- Sync Configuration -->
                {!! view_render_event('unopim.order.sync.manual.card.config.before') !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('order::app.admin.sync.manual.configuration')
                    </p>

                    <!-- Channel Selection -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.sync.manual.channel')
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
                            :label="trans('order::app.admin.sync.manual.channel')"
                            :placeholder="trans('order::app.admin.sync.manual.select-channel')"
                            track-by="id"
                            label-by="name"
                        />

                        <x-admin::form.control-group.error control-name="channel_id" />
                    </x-admin::form.control-group>

                    <!-- Sync Mode -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('order::app.admin.sync.manual.mode')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="mode"
                            name="mode"
                            rules="required"
                            :value="old('mode', 'incremental')"
                            :label="trans('order::app.admin.sync.manual.mode')"
                        >
                            <option value="incremental" selected>
                                @lang('order::app.admin.sync.manual.mode-incremental')
                            </option>
                            <option value="full">
                                @lang('order::app.admin.sync.manual.mode-full')
                            </option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="mode" />

                        <x-admin::form.control-group.hint>
                            @lang('order::app.admin.sync.manual.mode-hint')
                        </x-admin::form.control-group.hint>
                    </x-admin::form.control-group>

                    <!-- Date Range (Optional) -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.sync.manual.date-from')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="datetime-local"
                            id="date_from"
                            name="date_from"
                            :value="old('date_from')"
                            :label="trans('order::app.admin.sync.manual.date-from')"
                        />

                        <x-admin::form.control-group.error control-name="date_from" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('order::app.admin.sync.manual.date-to')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="datetime-local"
                            id="date_to"
                            name="date_to"
                            :value="old('date_to')"
                            :label="trans('order::app.admin.sync.manual.date-to')"
                        />

                        <x-admin::form.control-group.error control-name="date_to" />

                        <x-admin::form.control-group.hint>
                            @lang('order::app.admin.sync.manual.date-hint')
                        </x-admin::form.control-group.hint>
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.order.sync.manual.card.config.after') !!}
            </div>

            <!-- Right Column - Info -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">

                <!-- Info Box -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded box-shadow border border-blue-200 dark:border-blue-700">
                    <p class="mb-2 text-base text-blue-800 dark:text-blue-200 font-semibold">
                        @lang('order::app.admin.sync.manual.info-title')
                    </p>
                    <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2 list-disc list-inside">
                        <li>@lang('order::app.admin.sync.manual.info-1')</li>
                        <li>@lang('order::app.admin.sync.manual.info-2')</li>
                        <li>@lang('order::app.admin.sync.manual.info-3')</li>
                        <li>@lang('order::app.admin.sync.manual.info-4')</li>
                    </ul>
                </div>

                <!-- Warning Box -->
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900 rounded box-shadow border border-yellow-200 dark:border-yellow-700">
                    <p class="mb-2 text-base text-yellow-800 dark:text-yellow-200 font-semibold">
                        @lang('order::app.admin.sync.manual.warning-title')
                    </p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        @lang('order::app.admin.sync.manual.warning-message')
                    </p>
                </div>
            </div>
        </div>

        {!! view_render_event('unopim.order.sync.manual.form.after') !!}

    </x-admin::form>

    {!! view_render_event('unopim.order.sync.manual.after') !!}

</x-admin::layouts>
