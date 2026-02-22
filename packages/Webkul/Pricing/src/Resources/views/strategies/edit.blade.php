<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.strategies.edit.title')
    </x-slot>

    <v-edit-pricing-strategy :strategy="{{ json_encode($strategy) }}"></v-edit-pricing-strategy>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-edit-pricing-strategy-template">
            {!! view_render_event('unopim.admin.pricing.strategies.edit.before') !!}

            <x-admin::form
                :action="route('admin.pricing.strategies.update', $strategy->id)"
                method="PUT"
            >
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('pricing::app.strategies.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a
                            href="{{ route('admin.pricing.strategies.index') }}"
                            class="transparent-button"
                        >
                            @lang('pricing::app.strategies.edit.back-btn')
                        </a>

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('pricing::app.strategies.edit.save-btn')
                        </button>
                    </div>
                </div>

                <div class="flex gap-2.5 mt-3.5">
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('pricing::app.strategies.edit.general')
                            </p>

                            <!-- Scope Type (readonly) -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.edit.scope-type')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    :value="trans('pricing::app.strategies.edit.scopes.' . $strategy->scope_type)"
                                    readonly
                                    class="cursor-not-allowed"
                                />
                            </x-admin::form.control-group>

                            <!-- Scope ID (readonly) -->
                            @if ($strategy->scope_type !== 'global')
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('pricing::app.strategies.edit.scope-id')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $scopeLabel = match($strategy->scope_type) {
                                            'category' => $strategy->category?->code ?? $strategy->scope_id,
                                            'channel' => $strategy->channel?->code ?? $strategy->scope_id,
                                            'product' => $strategy->product?->sku ?? $strategy->scope_id,
                                            default => $strategy->scope_id
                                        };
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="text"
                                        :value="$scopeLabel"
                                        readonly
                                        class="cursor-not-allowed"
                                    />
                                </x-admin::form.control-group>
                            @endif

                            <!-- Margin Percentages -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.edit.minimum-margin')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="minimum_margin_percentage"
                                    rules="required|decimal:2"
                                    :value="old('minimum_margin_percentage', $strategy->minimum_margin_percentage)"
                                    :label="trans('pricing::app.strategies.edit.minimum-margin')"
                                />

                                <x-admin::form.control-group.error control-name="minimum_margin_percentage" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.edit.target-margin')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="target_margin_percentage"
                                    rules="required|decimal:2"
                                    :value="old('target_margin_percentage', $strategy->target_margin_percentage)"
                                    :label="trans('pricing::app.strategies.edit.target-margin')"
                                />

                                <x-admin::form.control-group.error control-name="target_margin_percentage" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.edit.premium-margin')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="premium_margin_percentage"
                                    rules="required|decimal:2"
                                    :value="old('premium_margin_percentage', $strategy->premium_margin_percentage)"
                                    :label="trans('pricing::app.strategies.edit.premium-margin')"
                                />

                                <x-admin::form.control-group.error control-name="premium_margin_percentage" />
                            </x-admin::form.control-group>

                            <!-- Psychological Pricing -->
                            <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2">
                                <x-admin::form.control-group.control
                                    type="checkbox"
                                    id="psychological_pricing"
                                    name="psychological_pricing"
                                    value="1"
                                    for="psychological_pricing"
                                    :checked="(bool) old('psychological_pricing', $strategy->psychological_pricing)"
                                />

                                <label
                                    class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                    for="psychological_pricing"
                                >
                                    @lang('pricing::app.strategies.edit.psychological-pricing')
                                </label>
                            </x-admin::form.control-group>

                            <!-- Round To -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('pricing::app.strategies.edit.round-to')
                                </x-admin::form.control-group.label>

                                @php
                                    $roundOptions = [
                                        ['id' => '0.99', 'label' => '0.99'],
                                        ['id' => '0.95', 'label' => '0.95'],
                                        ['id' => '0.00', 'label' => trans('pricing::app.strategies.edit.round-to-whole')],
                                        ['id' => 'none', 'label' => trans('pricing::app.strategies.edit.no-rounding')],
                                    ];
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="round_to"
                                    :value="old('round_to', $strategy->round_to)"
                                    :label="trans('pricing::app.strategies.edit.round-to')"
                                    :options="json_encode($roundOptions)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="round_to" />
                            </x-admin::form.control-group>

                            <!-- Is Active -->
                            <x-admin::form.control-group class="flex gap-2.5 items-center !mb-2">
                                <x-admin::form.control-group.control
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    for="is_active"
                                    :checked="(bool) old('is_active', $strategy->is_active)"
                                />

                                <label
                                    class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                    for="is_active"
                                >
                                    @lang('pricing::app.strategies.edit.is-active')
                                </label>
                            </x-admin::form.control-group>

                            <!-- Priority -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.edit.priority')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="priority"
                                    rules="required|integer"
                                    :value="old('priority', $strategy->priority)"
                                    :label="trans('pricing::app.strategies.edit.priority')"
                                />

                                <x-admin::form.control-group.error control-name="priority" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>
            </x-admin::form>

            {!! view_render_event('unopim.admin.pricing.strategies.edit.after') !!}
        </script>

        <script type="module">
            app.component('v-edit-pricing-strategy', {
                template: '#v-edit-pricing-strategy-template',
                props: ['strategy']
            });
        </script>
    @endPushOnce
</x-admin::layouts>
