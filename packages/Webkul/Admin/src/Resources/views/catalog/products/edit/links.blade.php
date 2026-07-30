@props([
    'associationTypes' => [],
])

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.before', ['product' => $product]) !!}

<x-admin::product.section-drawer
    id="associations"
    :title="trans('admin::app.catalog.products.edit.links.title')"
    :subtitle="trans('admin::app.catalog.products.edit.workspace.associations.subtitle')"
    icon="icon-product"
    form-id="product-edit-form"
    form-fields='[name^="associations["]'
>
    <x-slot:toggle>
        <x-admin::product.section-card
            id="associations"
            :title="trans('admin::app.catalog.products.edit.links.title')"
            icon="icon-product"
        >
            {{-- v-text attribute is SINGLE-quoted so @json's double quotes don't collide --}}
            <span v-text='($productWorkspace?.getCount("associations") ?? 0) + " " + @json(trans("admin::app.catalog.products.edit.workspace.associations.linked"))'></span>
        </x-admin::product.section-card>
    </x-slot:toggle>

    <x-slot:content>
        <v-product-links :association-types='@json($associationTypes)'></v-product-links>
    </x-slot:content>

    <x-slot:footer>
        <button
            type="button"
            class="primary-button"
            @click="close"
        >
            @lang('admin::app.catalog.products.edit.workspace.add-selected')
        </button>
    </x-slot:footer>
</x-admin::product.section-drawer>

{!! view_render_event('unopim.admin.catalog.product.edit.form.links.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-links-template"
    >
        <div
            class="grid gap-2.5"
            data-section-id="associations"
        >
            <!-- Panel -->
            <div class="bg-white grid gap-2.5 min-w-0 p-4 dark:bg-cherry-900 rounded box-shadow">
                <div class="flex justify-end items-center">
                    <!-- Add Association Type -->
                    <button
                        type="button"
                        class="secondary-button text-xs"
                        @click="$refs.typeSearch.open()"
                    >
                        @lang('admin::app.catalog.products.edit.links.add-type-btn')
                    </button>
                </div>

                <!-- No association types linked yet -->
                <div
                    class="grid gap-3.5 justify-center justify-items-center py-10 px-2.5"
                    v-if="! localTypes.length"
                >
                    <img
                        src="{{ unopim_asset('images/icon-add-product.svg') }}"
                        class="w-20 h-20 dark:invert dark:mix-blend-exclusion"
                    />

                    <div class="flex flex-col gap-1.5 items-center">
                        <p class="text-base text-gray-400 font-semibold">
                            @lang('admin::app.catalog.products.edit.links.no-types-title')
                        </p>

                        <p class="text-gray-400">
                            @lang('admin::app.catalog.products.edit.links.no-types-info')
                        </p>
                    </div>
                </div>

                <!-- Association Type Switcher -->
                <div
                    class="flex items-end gap-1 min-w-0 mb-2 border-b border-gray-200 dark:border-cherry-800"
                    ref="tabBar"
                    v-else
                >
                    <div
                        class="flex items-end gap-1 min-w-0 overflow-hidden"
                        ref="tabStrip"
                        role="tablist"
                        aria-label="@lang('admin::app.catalog.products.edit.links.title')"
                    >
                        <button
                            type="button"
                            role="tab"
                            v-for="type in tabTypes"
                            :key="'assoc-tab-' + type.code"
                            :aria-selected="type.code === activeTypeCode"
                            :class="type.code === activeTypeCode
                                ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                                : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white'"
                            class="flex shrink-0 items-center gap-1.5 whitespace-nowrap px-3 py-2 text-sm font-medium border-b-2 transition-all"
                            @click="selectType(type.code)"
                        >
                            <span v-text="type.name"></span>

                            <span
                                class="px-1.5 rounded-full bg-gray-100 dark:bg-cherry-800 text-xs"
                                v-if="type.links.length"
                                v-text="type.links.length"
                            ></span>
                        </button>
                    </div>

                    <div
                        ref="moreTypes"
                        v-show="overflowTypes.length"
                    >
                        <x-admin::form.searchable-menu
                            ::items="overflowMenuItems"
                            search-placeholder="{{ trans('admin::app.catalog.products.edit.links.search-types') }}"
                            @update:model-value="selectType($event)"
                        >
                            <x-slot:toggle>
                                <button
                                    type="button"
                                    class="whitespace-nowrap px-3 py-2 text-sm font-medium border-b-2"
                                    :class="isOverflowActive
                                        ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                                        : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white'"
                                >
                                    @lang('admin::app.catalog.products.edit.links.more-types')
                                </button>
                            </x-slot>
                        </x-admin::form.searchable-menu>
                    </div>
                </div>

                {{--
                    Every type stays rendered: its hidden inputs (the `__present`
                    sentinel and each link's values) must reach the request no
                    matter which tab is showing, or switching away from a type
                    would silently drop the edits made under it.
                --}}
                <div
                    v-for="type in localTypes"
                    :key="type.code"
                    v-show="type.code === activeTypeCode"
                >
                    {{--
                        Presence sentinel: emitted once per RENDERED type,
                        UNCONDITIONALLY (regardless of link count), so the
                        `associations[<typeCode>]` key always survives form
                        submission -- even after every link of this type is
                        removed. `AbstractType::prepareRichAssociations()` treats
                        any type key present in the submitted `associations`
                        payload as authoritative for that type (an empty/
                        sentinel-only value prunes all its `product_associations`
                        rows) and strips this sentinel before processing rows.
                        Types the product does not link to are never rendered, so
                        their links are never touched.
                    --}}
                    <input
                        type="hidden"
                        :name="'associations[' + type.code + '][__present]'"
                        value="1"
                    />

                    <div class="flex gap-5 justify-end items-center">
                        <!-- Add Product -->
                        <button
                            type="button"
                            class="secondary-button text-xs"
                            @click="openProductSearch(type.code)"
                        >
                            @lang('admin::app.catalog.products.edit.links.add-btn')
                        </button>
                    </div>

                    <!-- Product Listing -->
                    <div
                        class="grid"
                        v-if="type.links.length"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center gap-2.5 sm:justify-between p-4 border-b border-gray-200 dark:border-cherry-800"
                            v-for="(link, index) in type.links"
                            :key="link.sku"
                        >
                            <input
                                type="hidden"
                                :name="'associations[' + type.code + '][' + index + '][sku]'"
                                :value="link.sku"
                            />

                            <div
                                class="flex gap-2.5"
                                style="width: 240px; max-width: 100%; flex-shrink: 0"
                            >
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
                                <div class="grid gap-1.5 place-content-start min-w-0">
                                    <p
                                        class="text-base text-gray-800 dark:text-white font-semibold truncate"
                                        :title="getProductName(link)"
                                        v-text="getProductName(link)"
                                    >
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300 truncate">
                                        @{{ "@lang('admin::app.catalog.products.edit.links.sku')".replace(':sku', link.sku) }}
                                    </p>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700 font-medium transition-all"
                                        @click="remove(type.code, link)"
                                        title="@lang('admin::app.catalog.products.edit.links.remove-product')"
                                        aria-label="@lang('admin::app.catalog.products.edit.links.remove-product')"
                                    >
                                        <span class="icon-delete text-base"></span>

                                        @lang('admin::app.catalog.products.edit.links.remove-product')
                                    </button>
                                </div>
                            </div>

                            <!-- Custom Association Fields -->
                            <div
                                class="flex flex-wrap items-start gap-x-4 gap-y-3 flex-1 min-w-0"
                                v-if="(type.fields || []).length"
                            >
                                <x-admin::associations.link-fields />
                            </div>
                        </div>
                    </div>

                    <!-- For Empty Links -->
                    <div
                        class="grid gap-3.5 justify-center justify-items-center py-10 px-2.5"
                        v-else
                    >
                        <img
                            src="{{ unopim_asset('images/icon-add-product.svg') }}"
                            class="w-20 h-20 dark:invert dark:mix-blend-exclusion"
                        />

                        <div class="flex flex-col gap-1.5 items-center">
                            <p class="text-base text-gray-400 font-semibold">
                                @lang('admin::app.catalog.products.edit.links.empty-title')
                            </p>

                            <p class="text-gray-400">
                                @{{ @json(trans('admin::app.catalog.products.edit.links.empty-info')).replace(':type', type.name) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Teleported out of the drawer (z-index 9999): inside it the picker can never paint above the app header. --}}
            <teleport to="body">
                <x-admin::associations.product-picker
                    ref="productSearch"
                    ::added-product-ids="addedProductIds"
                    @onProductAdded="addSelected($event)"
                />

                <x-admin::associations.type-search
                    ref="typeSearch"
                    ::added-type-codes="addedTypeCodes"
                    ::locked-type-codes="linkedTypeCodes"
                    @onTypeAdded="syncTypes($event)"
                />
            </teleport>

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

                    activeTypeCode: null,

                    /**
                     * Mutable working copy of the `associationTypes` prop. Named
                     * differently from the prop itself: writing to
                     * `this.associationTypes` directly would silently no-op
                     * (Vue 3 exposes props as a readonly proxy), so switching,
                     * add/remove and link edits mutate `localTypes` instead. Only
                     * the types this product already links to arrive here; more
                     * are appended via the async picker (`addType`).
                     */
                    localTypes: JSON.parse(JSON.stringify(this.associationTypes)),

                    currentLocaleCode: "{{ core()->getRequestedLocaleCode() }}",

                    currentChannelCode: "{{ core()->getRequestedChannelCode() }}",

                    /**
                     * How many tabs fit the strip on the current viewport; `null`
                     * means "not measured yet", which renders every tab so their
                     * widths can be read off the DOM.
                     */
                    visibleTabCount: null,

                    tabGap: 4,
                }
            },

            computed: {
                selectedType() {
                    return this.localTypes.find(type => type.code === this.selectedTypeCode);
                },

                addedProductIds() {
                    const productIds = (this.selectedType?.links || []).map(link => link.id);

                    productIds.push(this.currentProduct.id);

                    return productIds;
                },

                tabTypes() {
                    return this.visibleTabCount === null
                        ? this.localTypes
                        : this.localTypes.slice(0, this.visibleTabCount);
                },

                overflowTypes() {
                    return this.visibleTabCount === null
                        ? []
                        : this.localTypes.slice(this.visibleTabCount);
                },

                /**
                 * The strip keeps its order when a tab is picked -- so a type chosen
                 * from the overflow menu stays there, and the toggle carries the
                 * active styling in its place.
                 */
                isOverflowActive() {
                    return this.overflowTypes.some(type => type.code === this.activeTypeCode);
                },

                overflowMenuItems() {
                    return this.overflowTypes.map(type => ({
                        id:    type.code,
                        label: type.name,
                        badge: type.links.length || null,
                    }));
                },

                addedTypeCodes() {
                    return this.localTypes.map(type => type.code);
                },

                linkedTypeCodes() {
                    return this.localTypes.filter(type => type.links.length).map(type => type.code);
                },
            },

            watch: {
                'localTypes.length'() {
                    this.measureTabs();
                },
            },

            mounted() {
                this.activeTypeCode = (this.localTypes.find(type => type.links.length) ?? this.localTypes[0])?.code ?? null;

                this.publishState();

                this.$nextTick(() => {
                    this.measureTabs();

                    if (window.ResizeObserver && this.$refs.tabBar) {
                        this._tabObserver = new ResizeObserver(() => this.measureTabs());
                        this._tabObserver.observe(this.$refs.tabBar);
                    }
                });
            },

            beforeUnmount() {
                this._tabObserver?.disconnect();
            },

            methods: {
                /**
                 * Fits as many tabs as the strip can hold before handing the rest to
                 * the "More" menu: every tab is rendered first so its rendered width
                 * can be read, then the row is cut to what the bar actually fits
                 * (keeping room for the menu toggle itself).
                 */
                async measureTabs() {
                    this.visibleTabCount = null;

                    await this.$nextTick();

                    const bar = this.$refs.tabBar;
                    const strip = this.$refs.tabStrip;

                    if (! bar || ! strip) {
                        return;
                    }

                    const widths = [...strip.children].map(tab => tab.offsetWidth);
                    const total = widths.reduce((sum, width) => sum + width + this.tabGap, 0);

                    if (total <= bar.clientWidth) {
                        this.visibleTabCount = widths.length;

                        return;
                    }

                    this.visibleTabCount = this.countFittingTabs(widths, bar.clientWidth - (this._moreWidth || 90));

                    await this.$nextTick();

                    const moreWidth = this.$refs.moreTypes?.offsetWidth;

                    if (moreWidth && moreWidth !== this._moreWidth) {
                        this._moreWidth = moreWidth;

                        this.visibleTabCount = this.countFittingTabs(widths, bar.clientWidth - moreWidth);
                    }
                },

                countFittingTabs(widths, available) {
                    let used = 0;
                    let fits = 0;

                    for (const width of widths) {
                        used += width + this.tabGap;

                        if (used > available) {
                            break;
                        }

                        fits++;
                    }

                    return Math.max(1, fits);
                },

                /**
                 * Linked products are rendered as hidden `associations[<typeCode>][<index>][sku]`
                 * inputs (see the `sku` hidden input above), one per link row across every
                 * association type -- scoped to this panel via `data-section-id="associations"`
                 * so the count reflects only this section's DOM.
                 */
                publishState() {
                    const total = document.querySelectorAll(
                        '[data-section-id="associations"] input[name^="associations["][name$="][sku]"]'
                    ).length;

                    this.$productWorkspace.setCount('associations', total);
                },

                openProductSearch(typeCode) {
                    this.selectedTypeCode = typeCode;

                    this.$refs.productSearch.open();
                },

                selectType(typeCode) {
                    this.activeTypeCode = typeCode;
                },

                /**
                 * Append picker-ticked types as fresh, link-less tabs (deduped by
                 * code) and drop the link-less ones the user unticked. No
                 * `setDirty` here: an empty type prunes nothing on save; the form
                 * turns dirty once a link is added.
                 */
                syncTypes({ added, removed }) {
                    removed
                        .filter(code => ! this.linkedTypeCodes.includes(code))
                        .forEach(code => {
                            const index = this.localTypes.findIndex(type => type.code === code);

                            if (index !== -1) {
                                this.localTypes.splice(index, 1);
                            }
                        });

                    added.forEach(type => {
                        if (this.localTypes.some(existing => existing.code === type.code)) {
                            return;
                        }

                        this.localTypes.push({
                            code:   type.code,
                            name:   type.name,
                            fields: type.fields || [],
                            links:  [],
                        });
                    });

                    if (added.length) {
                        this.activeTypeCode = added[0].code;
                    } else if (! this.localTypes.some(type => type.code === this.activeTypeCode)) {
                        this.activeTypeCode = this.localTypes[0]?.code ?? null;
                    }

                    this.$nextTick(() => {
                        this.publishState();

                        this.measureTabs();
                    });
                },

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
                        this.touchSection();

                        type.links = [...type.links, ...newLinks];
                    }

                    this.$nextTick(() => this.markDirty());
                },

                touchSection() {
                    this.$emitter.emit('section-drawer:touch', 'associations');
                },

                markDirty() {
                    this.publishState();

                    this.$productWorkspace.setDirty('associations', true);

                    this.touchSection();
                },

                remove(typeCode, link) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            const type = this.localTypes.find(type => type.code === typeCode);

                            this.touchSection();

                            if (type) {
                                type.links = type.links.filter(item => item.sku !== link.sku);
                            }

                            this.$nextTick(() => this.markDirty());
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
