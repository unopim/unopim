<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.recommendations.show.title')
    </x-slot>

    <v-price-recommendations></v-price-recommendations>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-price-recommendations-template">
            <div>
                <div class="flex justify-between items-center mb-4">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        @lang('pricing::app.recommendations.show.title')
                    </p>
                </div>

                <!-- Product Selection -->
                <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded mb-4">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('pricing::app.recommendations.show.select-product')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('pricing::app.recommendations.show.product')
                        </x-admin::form.control-group.label>

                        <x-admin::products.search
                            name="product_id"
                            @change="onProductChange"
                        />
                    </x-admin::form.control-group>
                </div>

                <!-- Recommendations Display -->
                <div v-if="recommendations.length > 0">
                    <div
                        v-for="rec in recommendations"
                        :key="rec.channelId"
                        class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded mb-4"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <p class="text-lg text-gray-800 dark:text-white font-semibold">
                                @{{ rec.channelName }}
                            </p>
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-sm">
                                @lang('pricing::app.recommendations.show.break-even'): @{{ rec.currency }} @{{ parseFloat(rec.breakEvenPrice).toFixed(2) }}
                            </span>
                        </div>

                        <!-- Three-Tier Pricing Cards -->
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Minimum Tier -->
                            <div class="p-4 border-2 border-gray-200 dark:border-cherry-700 rounded">
                                <div class="text-center mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        @lang('pricing::app.recommendations.show.minimum-tier')
                                    </p>
                                    <p class="text-3xl font-bold text-gray-800 dark:text-white">
                                        @{{ rec.currency }} @{{ parseFloat(rec.minimum.price).toFixed(2) }}
                                    </p>
                                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                                        @{{ parseFloat(rec.minimum.margin).toFixed(1) }}% @lang('pricing::app.recommendations.show.margin')
                                    </p>
                                </div>
                                <button
                                    @click="applyPrice(rec.channelId, 'minimum', rec.minimum.price)"
                                    class="w-full secondary-button"
                                >
                                    @lang('pricing::app.recommendations.show.apply')
                                </button>
                            </div>

                            <!-- Target Tier -->
                            <div class="p-4 border-2 border-violet-500 dark:border-violet-400 rounded bg-violet-50 dark:bg-violet-900/20">
                                <div class="text-center mb-3">
                                    <div class="flex justify-center items-center gap-2 mb-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            @lang('pricing::app.recommendations.show.target-tier')
                                        </p>
                                        <span class="px-2 py-0.5 bg-violet-500 text-white rounded text-xs">
                                            @lang('pricing::app.recommendations.show.recommended')
                                        </span>
                                    </div>
                                    <p class="text-3xl font-bold text-gray-800 dark:text-white">
                                        @{{ rec.currency }} @{{ parseFloat(rec.target.price).toFixed(2) }}
                                    </p>
                                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                                        @{{ parseFloat(rec.target.margin).toFixed(1) }}% @lang('pricing::app.recommendations.show.margin')
                                    </p>
                                </div>
                                <button
                                    @click="applyPrice(rec.channelId, 'target', rec.target.price)"
                                    class="w-full primary-button"
                                >
                                    @lang('pricing::app.recommendations.show.apply')
                                </button>
                            </div>

                            <!-- Premium Tier -->
                            <div class="p-4 border-2 border-gray-200 dark:border-cherry-700 rounded">
                                <div class="text-center mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        @lang('pricing::app.recommendations.show.premium-tier')
                                    </p>
                                    <p class="text-3xl font-bold text-gray-800 dark:text-white">
                                        @{{ rec.currency }} @{{ parseFloat(rec.premium.price).toFixed(2) }}
                                    </p>
                                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                                        @{{ parseFloat(rec.premium.margin).toFixed(1) }}% @lang('pricing::app.recommendations.show.margin')
                                    </p>
                                </div>
                                <button
                                    @click="applyPrice(rec.channelId, 'premium', rec.premium.price)"
                                    class="w-full secondary-button"
                                >
                                    @lang('pricing::app.recommendations.show.apply')
                                </button>
                            </div>
                        </div>

                        <!-- Strategy Info -->
                        <div v-if="rec.strategy" class="mt-4 p-3 bg-gray-50 dark:bg-cherry-800 rounded">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <strong>@lang('pricing::app.recommendations.show.strategy'):</strong>
                                @{{ rec.strategy }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-8 text-center bg-white dark:bg-cherry-900 box-shadow rounded">
                    <p class="text-gray-600 dark:text-gray-300">
                        @lang('pricing::app.recommendations.show.select-product-message')
                    </p>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-price-recommendations', {
                template: '#v-price-recommendations-template',

                data() {
                    return {
                        selectedProduct: null,
                        recommendations: []
                    };
                },

                methods: {
                    onProductChange(product) {
                        this.selectedProduct = product;
                        this.loadRecommendations();
                    },

                    loadRecommendations() {
                        if (!this.selectedProduct) return;

                        this.$axios.get('{{ route('admin.pricing.recommendations.show', '') }}', {
                            params: { product_id: this.selectedProduct.id }
                        })
                            .then(response => {
                                this.recommendations = response.data.recommendations;
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || '@lang('pricing::app.recommendations.show.error')'
                                });
                            });
                    },

                    applyPrice(channelId, tier, price) {
                        if (!confirm('@lang('pricing::app.recommendations.show.confirm-apply')')) {
                            return;
                        }

                        this.$axios.post('{{ route('admin.pricing.recommendations.apply', '') }}', {
                            product_id: this.selectedProduct.id,
                            channel_id: channelId,
                            tier: tier,
                            override_price: price
                        })
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || '@lang('pricing::app.recommendations.show.apply-error')'
                                });
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
