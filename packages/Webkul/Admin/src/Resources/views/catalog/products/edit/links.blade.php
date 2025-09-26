@props([
    'upSellAssociations'    => [],
    'crossSellAssociations' => [],
    'relatedAssociations'   => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.before', ['product' => $product]) !!}
    
<v-product-links></v-product-links>

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-links-template"
    >
        <div class="grid gap-2.5">
            <!-- Panel -->
            <div class="bg-white grid gap-2.5 p-4 dark:bg-cherry-900 rounded box-shadow">
                <p class="flex justify-between text-base text-gray-800 dark:text-white font-semibold mb-4">
                    @lang('admin::app.catalog.products.edit.links.title')
                </p>

                <div
                    class=""
                    v-for="type in types"
                >
                    <div class="flex gap-5 justify-between items-center">
                        <div class="flex flex-col gap-2">
                            <p
                                class="text-gray-800 text-xs dark:text-white font-medium"
                                v-text="type.title"
                            >
                            </p>
                        </div>
                        
                        <!-- Add Button -->
                        <div class="flex gap-x-1 items-center">
                            <div
                                class="secondary-button text-xs"
                                @click="selectedType = type.key; $refs.productSearch.openDrawer()"
                            >
                                @lang('admin::app.catalog.products.edit.links.add-btn')
                            </div>
                        </div>
                    </div>
        
                    <!-- Product Listing -->
                    <div
                        class="grid"
                        v-if="addedProducts[type.key]?.length"
                    >
                        <div
                            class="flex gap-2.5 justify-between p-4 border-b border-slate-300 dark:border-gray-800"
                            v-for="product in addedProducts[type.key]"
                        >
                            <!-- Hidden Input -->
                            <input
                                type="hidden"
                                :name="type.key + '[]'"
                                :value="product.sku"
                            />

                            <!-- Information -->
                            <div class="flex gap-2.5">
                                <!-- Image -->
                                <div
                                    class="w-full h-[60px] max-w-[60px] max-h-[60px] relative rounded overflow-hidden"
                                    :class="{'border border-dashed border-gray-300 dark:border-cherry-800 dark:invert dark:mix-blend-exclusion': ! product?.image, 'w-[60px]': product?.image}"
                                >
                                    <template v-if="! product?.image">
                                        <img src="{{ unopim_asset('images/product-placeholders/front.svg') }}">
                                    
                                        <p class="w-full absolute bottom-1.5 text-[6px] text-gray-400 text-center font-semibold">
                                            @lang('admin::app.catalog.products.edit.links.image-placeholder')
                                        </p>
                                    </template>
                
                                    <template v-else>
                                        <img :src="product?.image" class="w-full h-full object-cover object-top">
                                    </template>
                                </div>

                                <!-- Details -->
                                <div class="grid gap-1.5 place-content-start">
                                    <p
                                        class="text-base text-gray-800 dark:text-white font-semibold"
                                        v-text="product.name"
                                    >
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        @{{ "@lang('admin::app.catalog.products.edit.links.sku')".replace(':sku', product.sku) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="grid gap-1 place-content-start text-right">
                                <p
                                    class="text-red-600 cursor-pointer transition-all"
                                    @click="remove(type.key, product)"
                                    title="@lang('admin::app.catalog.products.index.datagrid.delete')"
                                >
                                    <i class="icon-delete text-red-600 cursor-pointer transition-all text-xl"></i>
                                </p>
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
                                @lang('admin::app.catalog.products.edit.links.empty-title')
                            </p>

                            <p
                                class="text-gray-400"
                                v-text="type.empty_info"
                            >
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Search Blade Component -->
            <x-admin::products.search
                ref="productSearch"
                ::added-product-ids="addedProductIds"
                ::queryParams='queryParams'
                @onProductAdded="addSelected($event)"
            />
        </div>
    </script>

    <script type="module">
        app.component('v-product-links', {
            template: '#v-product-links-template',

            data() {
                return {
                    currentProduct: @json($product),

                    selectedType: 'related_products',

                    types: [
                        {
                            key: 'related_products',
                            title: `@lang('admin::app.catalog.products.edit.links.related-products.title')`,
                            info: `@lang('admin::app.catalog.products.edit.links.related-products.info')`,
                            empty_info: `@lang('admin::app.catalog.products.edit.links.related-products.empty-info')`,
                        }, {
                            key: 'up_sells',
                            title: `@lang('admin::app.catalog.products.edit.links.up-sells.title')`,
                            info: `@lang('admin::app.catalog.products.edit.links.up-sells.info')`,
                            empty_info: `@lang('admin::app.catalog.products.edit.links.up-sells.empty-info')`,
                        }, {
                            key: 'cross_sells',
                            title: `@lang('admin::app.catalog.products.edit.links.cross-sells.title')`,
                            info: `@lang('admin::app.catalog.products.edit.links.cross-sells.info')`,
                            empty_info: `@lang('admin::app.catalog.products.edit.links.cross-sells.empty-info')`,
                        }
                    ],

                    addedProducts: {
                        'up_sells': @json($upSellAssociations ? $product->whereIn('sku', $upSellAssociations)->get()->map(fn ($item) => $item->normalizeWithImage()) : []),

                        'cross_sells': @json($crossSellAssociations ? $product->whereIn('sku', $crossSellAssociations)->get()->map(fn ($item) => $item->normalizeWithImage()) : []),

                        'related_products': @json($relatedAssociations ? $product->whereIn('sku', $relatedAssociations)->get()->map(fn ($item) => $item->normalizeWithImage()) : [])
                    },

                    queryParams: {
                        skipSku: "{{ $product->sku }}"
                    }
                }
            },

            computed: {
                addedProductIds() {
                    let productIds = this.addedProducts[this.selectedType].map(product => product.sku);

                    productIds.push(this.currentProduct.sku);

                    return productIds;
                }
            },

            methods: {
                addSelected(selectedProducts) {
                    const existingProducts = this.addedProducts[this.selectedType] || [];
                    const existingSkus = new Set(existingProducts.map(product => product.sku));
                    const newProducts = selectedProducts.filter(product => !existingSkus.has(product.sku));
                    
                    if (newProducts.length > 0) {
                        this.addedProducts[this.selectedType] = [...existingProducts, ...newProducts];
                    }
                },

                remove(type, product) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            this.addedProducts[type] = this.addedProducts[type].filter(item => item.sku !== product.sku);
                        },
                    });
                },

            }
        });
    </script>
@endPushOnce
