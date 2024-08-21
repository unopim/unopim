<v-product-search {{ $attributes }}></v-product-search>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-search-template"
    >
        <!-- Search Drawer -->
        <x-admin::drawer
            ref="searchProductDrawer"
            @close="searchTerm = ''; searchedProducts = [];"
        >
            <!-- Drawer Header -->
            <x-slot:header>
                <div class="grid gap-3">
                    <div class="flex justify-between items-center">
                        <p class="text-xl font-medium dark:text-white">
                            @lang('admin::app.components.products.search.title')
                        </p>

                        <div
                            class="ltr:mr-11 rtl:ml-11 primary-button"
                            @click="addSelected"
                        >
                            @lang('admin::app.components.products.search.add-btn')
                        </div>
                    </div>

                    <div class="relative w-full">
                        <input
                            type="text"
                            class="bg-white dark:bg-cherry-800 border dark:border-cherry-900 rounded-lg block w-full ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 py-1.5 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400"
                            placeholder="Search by sku"
                            v-model.lazy="searchTerm"
                            v-debounce="500"
                        />

                        <span class="icon-search text-2xl absolute ltr:right-3 rtl:left-3 top-1.5 flex items-center pointer-events-none"></span>
                    </div>
                </div>
            </x-slot>

            <!-- Drawer Content -->
            <x-slot:content class="!p-0">
                <div
                    class="grid"
                    v-if="filteredSearchedProducts.length"
                >
                    <div
                        class="flex gap-2.5 justify-between px-4 py-6 border-b border-slate-300 dark:border-gray-800"
                        v-for="product in filteredSearchedProducts"
                    >
                        <!-- Information -->
                        <div class="flex gap-2.5">
                            <!-- Checkbox -->
                            <div class="">
                                <input
                                    type="checkbox"
                                    class="sr-only peer"
                                    :id="'searched-product' + product.id"
                                    v-model="product.selected"
                                />

                                <label
                                    class="icon-checkbox-normal text-2xl peer-checked:icon-checkbox-check peer-checked:text-violet-700  cursor-pointer"
                                    :for="'searched-product' + product.id"
                                >
                                </label>
                            </div>

                            <!-- Image -->
                            <div
                                class="w-full h-[60px] max-w-[60px] max-h-[60px] relative rounded overflow-hidden"
                                :class="{'border border-dashed border-gray-300 dark:border-cherry-800 dark:invert dark:mix-blend-exclusion': ! product?.image, 'w-[60px]': product?.image}"
                            >
                                <template v-if="! product?.image">
                                    <img src="{{ unopim_asset('images/product-placeholders/front.svg') }}">
                                
                                    <p class="w-full absolute bottom-1.5 text-[6px] text-gray-400 text-center font-semibold">
                                        @lang('admin::app.components.products.search.product-image')
                                    </p>
                                </template>

                                <template v-else>
                                    <img :src="product?.image" class="w-full h-full object-cover object-top">
                                </template>
                            </div>

                            <!-- Details -->
                            <div class="grid gap-1.5 place-content-start">
                                <p class="text-base text-gray-800 dark:text-white font-semibold">
                                    @{{ product.name }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @{{ "@lang('admin::app.components.products.search.sku')".replace(':sku', product.sku) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- For Empty Variations -->
                <div
                    class="grid gap-3.5 justify-center justify-items-center py-10 px-2.5"
                    v-else
                >
                    <!-- Placeholder Image -->
                    <img
                        src="{{ unopim_asset('images/icon-add-product.svg') }}"
                        class="w-20 h-20 dark:invert dark:mix-blend-exclusion"
                    />

                    <!-- Add Variants Information -->
                    <div class="flex flex-col gap-1.5 items-center">
                        <p class="text-base text-gray-400 font-semibold">
                            @lang('admin::app.components.products.search.empty-title')
                        </p>

                        <p class="text-gray-400">
                            @lang('admin::app.components.products.search.empty-info')
                        </p>
                    </div>
                </div>
            </x-slot>
        </x-admin::drawer>
    </script>

    <script type="module">
        app.component('v-product-search', {
            template: '#v-product-search-template',

            props: {
                addedProductIds: {
                    type: Array,
                    default: []
                },

                queryParams: {
                    type: Object,
                    default: () => ({})
                },
            },

            data() {
                return {
                    searchTerm: '',

                    searchedProducts: [],
                }
            },

            computed: {
                filteredSearchedProducts() {
                    return this.searchedProducts.filter(product => ! this.addedProductIds.includes(product.id));
                }
            },

            watch: {
                searchTerm: function(newVal, oldVal) {
                    this.search()
                }
            },

            methods: {
                openDrawer() {
                    this.$refs.searchProductDrawer.open();
                },

                search() {
                    if (this.searchTerm.length <= 1) {
                        this.searchedProducts = [];

                        return;
                    }

                    let self = this;
                    
                    this.$axios.get("{{ route('admin.catalog.products.search') }}", {
                            params: {
                                ...{query: this.searchTerm},
                                ...this.queryParams
                            }
                        })
                        .then(function(response) {
                            self.searchedProducts = response.data.data;
                        })
                        .catch(function (error) {
                        })
                },

                addSelected() {
                    let selectedProducts = this.searchedProducts.filter(product => product.selected);

                    this.$emit('onProductAdded', selectedProducts);

                    this.$refs.searchProductDrawer.close();
                },
            }
        });
    </script>
@endPushOnce
