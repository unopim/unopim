<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.break-even.show.title')
    </x-slot>

    <v-break-even-calculator></v-break-even-calculator>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-break-even-calculator-template">
            <div>
                <div class="flex justify-between items-center mb-4">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        @lang('pricing::app.break-even.show.title')
                    </p>
                </div>

                <!-- Product Selection -->
                <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded mb-4">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('pricing::app.break-even.show.select-product')
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('pricing::app.break-even.show.product')
                            </x-admin::form.control-group.label>

                            <x-admin::products.search
                                name="product_id"
                                @change="onProductChange"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('pricing::app.break-even.show.channel')
                            </x-admin::form.control-group.label>

                            @php
                                $channels = app('Webkul\Core\Repositories\ChannelRepository')->all();
                                $channelOptions = $channels->map(fn($ch) => ['id' => $ch->id, 'label' => $ch->code])->toArray();
                            @endphp

                            <x-admin::form.control-group.control
                                type="select"
                                v-model="selectedChannel"
                                @change="calculateBreakEven"
                                :options="json_encode($channelOptions)"
                                track-by="id"
                                label-by="label"
                                :placeholder="trans('pricing::app.break-even.show.all-channels')"
                            />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <!-- Results Display -->
                <div v-if="result" class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('pricing::app.break-even.show.results')
                    </p>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <!-- Break-Even Price Card -->
                        <div class="p-4 bg-violet-50 dark:bg-cherry-800 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                @lang('pricing::app.break-even.show.break-even-price')
                            </p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                                @{{ result.currency }} @{{ parseFloat(result.breakEvenPrice).toFixed(2) }}
                            </p>
                        </div>

                        <!-- Fixed Costs Card -->
                        <div class="p-4 bg-blue-50 dark:bg-cherry-800 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                @lang('pricing::app.break-even.show.fixed-costs')
                            </p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                                @{{ result.currency }} @{{ parseFloat(result.fixedCosts).toFixed(2) }}
                            </p>
                        </div>

                        <!-- Variable Rate Card -->
                        <div class="p-4 bg-green-50 dark:bg-cherry-800 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                @lang('pricing::app.break-even.show.variable-rate')
                            </p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                                @{{ (parseFloat(result.variableRate) * 100).toFixed(2) }}%
                            </p>
                        </div>
                    </div>

                    <!-- Cost Breakdown -->
                    <div v-if="result.costBreakdown" class="overflow-x-auto">
                        <p class="mb-3 text-sm text-gray-700 dark:text-gray-300 font-semibold">
                            @lang('pricing::app.break-even.show.cost-breakdown')
                        </p>
                        <table class="w-full text-sm">
                            <thead class="bg-violet-50 dark:bg-cherry-800">
                                <tr>
                                    <th class="p-2 text-left">@lang('pricing::app.break-even.show.cost-type')</th>
                                    <th class="p-2 text-right">@lang('pricing::app.break-even.show.amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(amount, type) in result.costBreakdown" class="border-b dark:border-cherry-700">
                                    <td class="p-2 text-gray-700 dark:text-gray-300">@{{ type }}</td>
                                    <td class="p-2 text-right text-gray-700 dark:text-gray-300">
                                        @{{ result.currency }} @{{ parseFloat(amount).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center bg-white dark:bg-cherry-900 box-shadow rounded">
                    <p class="text-gray-600 dark:text-gray-300">
                        @lang('pricing::app.break-even.show.select-product-message')
                    </p>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-break-even-calculator', {
                template: '#v-break-even-calculator-template',

                data() {
                    return {
                        selectedProduct: null,
                        selectedChannel: null,
                        result: null
                    };
                },

                methods: {
                    onProductChange(product) {
                        this.selectedProduct = product;
                        this.calculateBreakEven();
                    },

                    calculateBreakEven() {
                        if (!this.selectedProduct) return;

                        const params = { product_id: this.selectedProduct.id };
                        if (this.selectedChannel) {
                            params.channel_id = this.selectedChannel;
                        }

                        this.$axios.get('{{ route('admin.pricing.break-even.show', '') }}', { params })
                            .then(response => {
                                this.result = response.data.result;
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || '@lang('pricing::app.break-even.show.error')'
                                });
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
