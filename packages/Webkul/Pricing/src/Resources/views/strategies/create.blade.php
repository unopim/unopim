<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.strategies.create.title')
    </x-slot>

    <v-create-pricing-strategy></v-create-pricing-strategy>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-create-pricing-strategy-template">
            {!! view_render_event('unopim.admin.pricing.strategies.create.before') !!}

            <x-admin::form
                :action="route('admin.pricing.strategies.store')"
            >
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('pricing::app.strategies.create.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a
                            href="{{ route('admin.pricing.strategies.index') }}"
                            class="transparent-button"
                        >
                            @lang('pricing::app.strategies.create.back-btn')
                        </a>

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('pricing::app.strategies.create.save-btn')
                        </button>
                    </div>
                </div>

                <div class="flex gap-2.5 mt-3.5">
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('pricing::app.strategies.create.general')
                            </p>

                            <!-- Scope Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.create.scope-type')
                                </x-admin::form.control-group.label>

                                @php
                                    $scopeTypes = [
                                        ['id' => 'global', 'label' => trans('pricing::app.strategies.create.scopes.global')],
                                        ['id' => 'category', 'label' => trans('pricing::app.strategies.create.scopes.category')],
                                        ['id' => 'channel', 'label' => trans('pricing::app.strategies.create.scopes.channel')],
                                        ['id' => 'product', 'label' => trans('pricing::app.strategies.create.scopes.product')],
                                    ];
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="scope_type"
                                    rules="required"
                                    v-model="scopeType"
                                    :value="old('scope_type')"
                                    :label="trans('pricing::app.strategies.create.scope-type')"
                                    :options="json_encode($scopeTypes)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="scope_type" />
                            </x-admin::form.control-group>

                            <!-- Scope ID (conditional based on scope_type) -->
                            <x-admin::form.control-group v-if="selectedScopeType !== 'global'">
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.create.scope-id')
                                </x-admin::form.control-group.label>

                                <!-- Category Scope -->
                                <div v-if="selectedScopeType === 'category'">
                                    @php
                                        $categories = app('Webkul\Category\Repositories\CategoryRepository')->all();
                                        $categoryOptions = $categories->map(fn($cat) => ['id' => $cat->id, 'label' => $cat->code])->toArray();
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="scope_id"
                                        rules="required"
                                        :label="trans('pricing::app.strategies.create.scope-id')"
                                        :options="json_encode($categoryOptions)"
                                        track-by="id"
                                        label-by="label"
                                    />
                                </div>

                                <!-- Channel Scope -->
                                <div v-if="selectedScopeType === 'channel'">
                                    @php
                                        $channels = app('Webkul\Core\Repositories\ChannelRepository')->all();
                                        $channelOptions = $channels->map(fn($ch) => ['id' => $ch->id, 'label' => $ch->code])->toArray();
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="scope_id"
                                        rules="required"
                                        :label="trans('pricing::app.strategies.create.scope-id')"
                                        :options="json_encode($channelOptions)"
                                        track-by="id"
                                        label-by="label"
                                    />
                                </div>

                                <!-- Product Scope -->
                                <div v-if="selectedScopeType === 'product'">
                                    <x-admin::products.search
                                        name="scope_id"
                                        rules="required"
                                        :label="trans('pricing::app.strategies.create.scope-id')"
                                    />
                                </div>

                                <x-admin::form.control-group.error control-name="scope_id" />
                            </x-admin::form.control-group>

                            <!-- Hidden scope_id for global -->
                            <input v-if="selectedScopeType === 'global'" type="hidden" name="scope_id" value="0">

                            <!-- Margin Percentages -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.create.minimum-margin')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="minimum_margin_percentage"
                                    rules="required|decimal:2"
                                    :value="old('minimum_margin_percentage')"
                                    :label="trans('pricing::app.strategies.create.minimum-margin')"
                                    :placeholder="trans('pricing::app.strategies.create.margin-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="minimum_margin_percentage" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.create.target-margin')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="target_margin_percentage"
                                    rules="required|decimal:2"
                                    :value="old('target_margin_percentage')"
                                    :label="trans('pricing::app.strategies.create.target-margin')"
                                    :placeholder="trans('pricing::app.strategies.create.margin-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="target_margin_percentage" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.create.premium-margin')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="premium_margin_percentage"
                                    rules="required|decimal:2"
                                    :value="old('premium_margin_percentage')"
                                    :label="trans('pricing::app.strategies.create.premium-margin')"
                                    :placeholder="trans('pricing::app.strategies.create.margin-placeholder')"
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
                                    :checked="(bool) old('psychological_pricing')"
                                />

                                <label
                                    class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                    for="psychological_pricing"
                                >
                                    @lang('pricing::app.strategies.create.psychological-pricing')
                                </label>
                            </x-admin::form.control-group>

                            <!-- Round To -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('pricing::app.strategies.create.round-to')
                                </x-admin::form.control-group.label>

                                @php
                                    $roundOptions = [
                                        ['id' => '0.99', 'label' => '0.99'],
                                        ['id' => '0.95', 'label' => '0.95'],
                                        ['id' => '0.00', 'label' => trans('pricing::app.strategies.create.round-to-whole')],
                                        ['id' => 'none', 'label' => trans('pricing::app.strategies.create.no-rounding')],
                                    ];
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="round_to"
                                    :value="old('round_to', '0.99')"
                                    :label="trans('pricing::app.strategies.create.round-to')"
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
                                    :checked="(bool) old('is_active', true)"
                                />

                                <label
                                    class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                                    for="is_active"
                                >
                                    @lang('pricing::app.strategies.create.is-active')
                                </label>
                            </x-admin::form.control-group>

                            <!-- Priority -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.strategies.create.priority')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="priority"
                                    rules="required|integer"
                                    :value="old('priority', 100)"
                                    :label="trans('pricing::app.strategies.create.priority')"
                                    :placeholder="trans('pricing::app.strategies.create.priority-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="priority" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>
            </x-admin::form>

            {!! view_render_event('unopim.admin.pricing.strategies.create.after') !!}
        </script>

        <script type="module">
            app.component('v-create-pricing-strategy', {
                template: '#v-create-pricing-strategy-template',

                data() {
                    return {
                        scopeType: @json(old('scope_type', 'global')),
                        selectedScopeType: @json(old('scope_type', 'global'))
                    };
                },

                watch: {
                    scopeType(value) {
                        this.selectedScopeType = this.parseValue(value)?.id || value;
                    }
                },

                methods: {
                    parseValue(value) {
                        try {
                            return value ? JSON.parse(value) : null;
                        } catch (error) {
                            return value;
                        }
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
