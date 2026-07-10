@props([
    'associationTypes' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.before', ['product' => $product]) !!}

<v-product-links :association-types='@json($associationTypes)'></v-product-links>

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

                @foreach ($associationTypes as $typeIndex => $type)
                    <div class="{{ $typeIndex > 0 ? 'pt-4 border-t border-slate-200 dark:border-gray-800' : '' }}">
                    <div class="flex gap-5 justify-between items-center">
                        <div class="flex flex-col gap-2">
                            <p class="text-gray-800 text-xs dark:text-white font-medium">
                                {{ $type['name'] }}
                            </p>
                        </div>

                        <!-- Add Button -->
                        <div class="flex gap-x-1 items-center">
                            <div
                                class="secondary-button text-xs"
                                @click="selectedTypeCode = '{{ $type['code'] }}'; $refs.productSearch.openDrawer()"
                            >
                                @lang('admin::app.catalog.products.edit.links.add-btn')
                            </div>
                        </div>
                    </div>

                    <!-- Product Listing -->
                    <div
                        class="grid"
                        v-if="localTypes[{{ $typeIndex }}]?.links?.length"
                    >
                        <div
                            class="flex gap-2.5 justify-between p-4 border-b border-slate-300 dark:border-gray-800"
                            v-for="(link, index) in localTypes[{{ $typeIndex }}].links"
                            :key="link.sku"
                        >
                            <!-- Hidden Input -->
                            <input
                                type="hidden"
                                :name="'associations[{{ $type['code'] }}][' + index + '][sku]'"
                                :value="link.sku"
                            />

                            <!-- Information -->
                            <div class="flex gap-2.5">
                                <!-- Image -->
                                <div
                                    class="w-full h-[60px] max-w-[60px] max-h-[60px] relative rounded overflow-hidden"
                                    :class="{'border border-dashed border-gray-300 dark:border-cherry-800 dark:invert dark:mix-blend-exclusion': ! link?.image, 'w-[60px]': link?.image}"
                                >
                                    <template v-if="! link?.image">
                                        <img src="{{ unopim_asset('images/product-placeholders/front.svg') }}">

                                        <p class="w-full absolute bottom-1.5 text-[6px] text-gray-400 text-center font-semibold">
                                            @lang('admin::app.catalog.products.edit.links.image-placeholder')
                                        </p>
                                    </template>

                                    <template v-else>
                                        <img :src="link?.image" class="w-full h-full object-cover object-top">
                                    </template>
                                </div>

                                <!-- Details -->
                                <div class="grid gap-1.5 place-content-start">
                                    <p
                                        class="text-base text-gray-800 dark:text-white font-semibold"
                                        v-text="getProductName(link)"
                                    >
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        @{{ "@lang('admin::app.catalog.products.edit.links.sku')".replace(':sku', link.sku) }}
                                    </p>
                                </div>
                            </div>

                            @if (! empty($type['fields']))
                                <!-- Custom Association Fields -->
                                <div class="grid gap-2.5 flex-1 max-w-[280px]">
                                    <x-admin::associations.link-fields
                                        :fields="$type['fields']"
                                        :type-code="$type['code']"
                                    />
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="grid gap-1 place-content-start text-right">
                                <p
                                    class="text-red-600 cursor-pointer transition-all"
                                    @click="remove('{{ $type['code'] }}', link)"
                                    title="@lang('admin::app.catalog.products.index.datagrid.delete')"
                                >
                                    <i class="icon-delete text-red-600 cursor-pointer transition-all text-xl"></i>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- For Empty Links -->
                    <div
                        class="grid gap-3.5 justify-center justify-items-center py-10 px-2.5"
                        v-else
                    >
                        <!-- Placeholder Image -->
                        <img
                            src="{{ unopim_asset('images/icon-add-product.svg') }}"
                            class="w-20 h-20 dark:invert dark:mix-blend-exclusion"
                        />

                        <!-- Add Links Information -->
                        <div class="flex flex-col gap-1.5 items-center">
                            <p class="text-base text-gray-400 font-semibold">
                                @lang('admin::app.catalog.products.edit.links.empty-title')
                            </p>

                            <p class="text-gray-400">
                                {{ trans('admin::app.catalog.products.edit.links.empty-info', ['type' => $type['name']]) }}
                            </p>
                        </div>
                    </div>
                    </div>
                @endforeach
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

            props: {
                associationTypes: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    currentProduct: @json($product),

                    selectedTypeCode: null,

                    /**
                     * Mutable working copy of the `associationTypes` prop. Named
                     * differently from the prop itself: writing to
                     * `this.associationTypes` directly would silently no-op
                     * (Vue 3 exposes props as a readonly proxy), so add/remove
                     * mutate `localTypes` instead.
                     */
                    localTypes: JSON.parse(JSON.stringify(this.associationTypes)),

                    currentLocaleCode: "{{ core()->getRequestedLocaleCode() }}",

                    currentChannelCode: "{{ core()->getRequestedChannelCode() }}",

                    queryParams: {
                        skipSku: "{{ $product->sku }}"
                    }
                }
            },

            computed: {
                selectedType() {
                    return this.localTypes.find(type => type.code === this.selectedTypeCode);
                },

                addedProductIds() {
                    let productIds = (this.selectedType?.links || []).map(link => link.sku);

                    productIds.push(this.currentProduct.sku);

                    return productIds;
                }
            },

            methods: {
                /**
                 * `Product::normalizeWithImage()` (the shape every link/searched
                 * product here is built from) has no `name` key. Derive a
                 * display name from `values`, following the same
                 * common/locale_specific/channel_locale_specific resolution
                 * order as attribute values elsewhere, falling back to the sku.
                 */
                getProductName(product) {
                    let values = product?.values || {};

                    let name = values.channel_locale_specific?.[this.currentChannelCode]?.[this.currentLocaleCode]?.name
                        ?? values.locale_specific?.[this.currentLocaleCode]?.name
                        ?? values.common?.name;

                    return name || product?.sku || '';
                },

                addSelected(selectedProducts) {
                    const type = this.selectedType;

                    if (! type) {
                        return;
                    }

                    const existingSkus = new Set(type.links.map(link => link.sku));

                    const newLinks = selectedProducts
                        .filter(product => ! existingSkus.has(product.sku))
                        .map(product => ({
                            ...product,
                            // A freshly added link has no stored custom-field
                            // data yet; seed empty buckets so `assocField*`
                            // lookups below don't have to guard against a
                            // missing `additional_data` on new rows.
                            additional_data: { common: {}, locale_specific: {} },
                        }));

                    if (newLinks.length > 0) {
                        type.links = [...type.links, ...newLinks];
                    }
                },

                remove(typeCode, link) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            const type = this.localTypes.find(type => type.code === typeCode);

                            if (type) {
                                type.links = type.links.filter(item => item.sku !== link.sku);
                            }
                        },
                    });
                },

                /**
                 * Builds the bracket-path `name` for one field of one link,
                 * e.g. `associations[bundle_kit][0][additional_data][common][quantity]`
                 * or, for a `value_per_locale` field,
                 * `associations[bundle_kit][0][additional_data][locale_specific][en_US][quantity]`.
                 */
                assocFieldName(typeCode, index, field) {
                    const bucket = field.value_per_locale
                        ? 'additional_data][locale_specific][' + this.currentLocaleCode
                        : 'additional_data][common';

                    return 'associations[' + typeCode + '][' + index + '][' + bucket + '][' + field.code + ']';
                },

                /**
                 * Raw stored value for one field of one link, read from the
                 * correct `additional_data` bucket (`common` or
                 * `locale_specific.<currentLocaleCode>`) so every displayed
                 * link's existing custom-field values are pre-filled (and, since
                 * the inputs are part of the submitted form, resubmitted as-is
                 * for links the user doesn't touch).
                 */
                assocFieldValue(link, field) {
                    const bucket = field.value_per_locale
                        ? (link.additional_data?.locale_specific?.[this.currentLocaleCode] || {})
                        : (link.additional_data?.common || {});

                    return bucket[field.code] ?? '';
                },

                assocFieldBoolean(link, field) {
                    return String(this.assocFieldValue(link, field)).toLowerCase() === 'true';
                },

                assocFieldChecked(link, field, optionCode) {
                    const raw = String(this.assocFieldValue(link, field) || '');

                    return raw.split(',').includes(optionCode);
                },

                assocFieldOption(link, field) {
                    const raw = this.assocFieldValue(link, field);

                    return (field.options || []).find(option => option.code === raw) || null;
                },

                assocFieldOptions(link, field) {
                    const raw = String(this.assocFieldValue(link, field) || '');
                    const codes = raw ? raw.split(',') : [];

                    return (field.options || []).filter(option => codes.includes(option.code));
                },

                /**
                 * Toggles one option of a checkbox field, mutating
                 * `link.additional_data`'s comma-joined string directly (the
                 * same bucket `assocFieldValue()`/`assocFieldChecked()` read
                 * from) so the single authoritative hidden input for this
                 * field always carries the up-to-date, comma-joined string
                 * of checked option codes -- never a bracket-array `name[]`.
                 */
                toggleAssocCheckboxOption(link, field, optionCode, isChecked) {
                    if (! link.additional_data) {
                        link.additional_data = { common: {}, locale_specific: {} };
                    }

                    let bucket;

                    if (field.value_per_locale) {
                        if (! link.additional_data.locale_specific) {
                            link.additional_data.locale_specific = {};
                        }

                        if (! link.additional_data.locale_specific[this.currentLocaleCode]) {
                            link.additional_data.locale_specific[this.currentLocaleCode] = {};
                        }

                        bucket = link.additional_data.locale_specific[this.currentLocaleCode];
                    } else {
                        if (! link.additional_data.common) {
                            link.additional_data.common = {};
                        }

                        bucket = link.additional_data.common;
                    }

                    let codes = String(bucket[field.code] || '').split(',').filter(Boolean);

                    if (isChecked) {
                        if (! codes.includes(optionCode)) {
                            codes.push(optionCode);
                        }
                    } else {
                        codes = codes.filter(code => code !== optionCode);
                    }

                    bucket[field.code] = codes.join(',');
                },
            }
        });
    </script>
@endPushOnce
