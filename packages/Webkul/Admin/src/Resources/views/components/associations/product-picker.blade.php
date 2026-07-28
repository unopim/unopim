{{--
    Product picker for the association Links panel. Wraps the real product
    DataGrid so the picker inherits its search, filters, sorting and column
    management rather than reimplementing a reduced search of its own.

    Emits `onProductAdded` with `normalizeWithImage()` payloads — the shape the
    Links panel stores a link from — resolved for the whole selection in one
    request once the user confirms.
--}}
<v-association-product-picker {{ $attributes }}></v-association-product-picker>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-association-product-picker-template"
    >
        {{--
            `no-class` drops the modal's centered-card chrome so the picker fills
            the whole window — a product grid with its own toolbar, filters and
            pagination needs the room.

            Chrome, grid and confirm button all live in the content slot as one
            flex column: as separate header/content/footer slots they stack
            without a shared height, so the panel could only be filled by
            hard-coding a viewport calculation that drifts the moment any row
            wraps, leaving the page showing through.
        --}}
        <x-admin::modal
            ref="pickerModal"
            :no-class="true"
        >
            <x-slot:content style="padding: 0; border: 0">
            {{--
                `h-screen` because the modal's own wrapper is auto-height, so `h-full`
                would collapse to the content; `w-full` rather than `w-screen` because
                the latter counts the scrollbar the fixed wrapper already excludes.
            --}}
            <div class="flex flex-col w-full h-screen bg-white dark:bg-cherry-900">
                <div class="shrink-0 flex items-center justify-between gap-2.5 px-6 py-4 border-b dark:border-gray-800">
                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                        @lang('admin::app.components.associations.product-picker.title')
                    </p>

                    <div class="flex items-center gap-3">
                        <p
                            class="text-xs text-gray-600 dark:text-gray-300"
                            v-text="selectionSummary"
                        ></p>

                        <span
                            class="icon-cancel text-3xl cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 hover:rounded-md"
                            @click="toggle"
                        ></span>
                    </div>
                </div>

                <div class="flex-1 min-h-0 overflow-auto px-6 py-4">

                <x-admin::datagrid
                    :src="route('admin.catalog.products.index')"
                    storage-key="association-product-picker"
                    :filter-attributes-src="route('admin.catalog.products.filterable_attributes')"
                    scope-channel="{{ core()->getRequestedChannelCode() }}"
                    scope-locale="{{ core()->getRequestedLocaleCode() }}"
                    ref="datagrid"
                >
                    <template #header="{ columns, isLoading }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.head />
                        </template>

                        <div
                            v-else
                            class="row grid gap-2.5 min-h-[47px] items-center px-4 py-2.5 border-b dark:border-cherry-800 bg-primary-50 dark:bg-cherry-800 text-gray-600 dark:text-gray-300 font-semibold"
                            :style="gridTemplate(columns)"
                        >
                            <span></span>

                            <p
                                class="min-w-0 overflow-hidden text-ellipsis text-nowrap"
                                v-for="column in pickerColumns(columns)"
                                :key="column.index"
                                :title="column.label"
                                v-text="column.label"
                            ></p>
                        </div>
                    </template>

                    <template #body="{ columns, records, meta, isLoading }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.body />
                        </template>

                        <template v-else-if="selectableRecords(records, meta).length">
                            {{--
                                `toggleRecord` (not `toggle`): the enclosing modal
                                exposes its own `toggle` through the content slot's
                                scope, which would otherwise shadow this component's
                                method and close the picker on every row click.
                            --}}
                            <div
                                class="row grid gap-2.5 items-center px-4 py-4 cursor-pointer border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                v-for="record in selectableRecords(records, meta)"
                                :key="record[meta.primary_column]"
                                :style="gridTemplate(columns)"
                                @click="toggleRecord(record, meta)"
                            >
                                <p @click.stop>
                                    <label :for="'assoc-pick-' + record[meta.primary_column]">
                                        <input
                                            type="checkbox"
                                            class="peer hidden"
                                            :id="'assoc-pick-' + record[meta.primary_column]"
                                            :checked="isSelected(record, meta)"
                                            @change="toggleRecord(record, meta)"
                                        />

                                        <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-primary-700 cursor-pointer rounded-md text-2xl"></span>
                                    </label>
                                </p>

                                <div
                                    class="min-w-0"
                                    v-for="column in pickerColumns(columns)"
                                    :key="column.index"
                                >
                                    <img
                                        class="h-[120px] max-w-[60px] min-w-[60px] max-h-[60px] min-h-[60px] rounded-lg border border-gray-300 shadow-sm object-cover"
                                        v-if="column.type === 'image'"
                                        :src="record[column.index] || '{{ unopim_asset('images/placeholder.svg') }}'"
                                        alt="@lang('admin::app.components.datagrid.table.thumbnail')"
                                    />

                                    {{-- Only closure columns emit trusted server-rendered HTML; everything else is plain text to prevent stored XSS. --}}
                                    <p
                                        class="truncate"
                                        v-else-if="column.closure"
                                        :title="stripHtml(record[column.index])"
                                        v-html="record[column.index]"
                                    ></p>

                                    <p
                                        class="truncate"
                                        v-else
                                        :title="stripHtml(record[column.index])"
                                        v-text="record[column.index]"
                                    ></p>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="row grid px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center">
                                <p>
                                    @lang('admin::app.components.datagrid.table.no-records-available')
                                </p>
                            </div>
                        </template>
                    </template>
                </x-admin::datagrid>
                </div>

                <div class="shrink-0 flex justify-end px-6 py-4 border-t dark:border-gray-800">
                    <button
                        type="button"
                        class="primary-button"
                        :disabled="! selectedIds.length"
                        @click="addSelected"
                    >
                        @lang('admin::app.components.associations.product-picker.add-btn')
                    </button>
                </div>
            </div>
            </x-slot>
        </x-admin::modal>
    </script>

    <script type="module">
        app.component('v-association-product-picker', {
            template: '#v-association-product-picker-template',

            props: {
                addedProductIds: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    selectedIds: [],
                };
            },

            computed: {
                selectionSummary() {
                    return "@lang('admin::app.components.associations.product-picker.selected')"
                        .replace(':count', this.selectedIds.length);
                },
            },

            methods: {
                open() {
                    this.selectedIds = [];

                    this.$refs.pickerModal.open();
                },

                /**
                 * Header and body both render from this list so the two grids stay
                 * aligned, and it follows the same `visible` flag the core grid
                 * does so the toolbar's column manager governs the picker too.
                 */
                pickerColumns(columns) {
                    return (columns || []).filter(column => column.visible !== false);
                },

                gridTemplate(columns) {
                    return `grid-template-columns: 40px repeat(${this.pickerColumns(columns).length}, minmax(80px, 1fr))`;
                },

                stripHtml(value) {
                    return String(value ?? '').replace(/<[^>]*>/g, '');
                },

                recordId(record, meta) {
                    return record[meta.primary_column];
                },

                /**
                 * The product being edited and anything already linked under the
                 * active type are dropped, so the grid never offers a row that the
                 * panel would silently discard.
                 */
                selectableRecords(records, meta) {
                    return (records || []).filter(
                        record => ! this.addedProductIds.includes(this.recordId(record, meta))
                    );
                },

                isSelected(record, meta) {
                    return this.selectedIds.includes(this.recordId(record, meta));
                },

                toggleRecord(record, meta) {
                    const id = this.recordId(record, meta);

                    this.selectedIds = this.isSelected(record, meta)
                        ? this.selectedIds.filter(selected => selected !== id)
                        : [...this.selectedIds, id];
                },

                addSelected() {
                    const ids = this.selectedIds.filter(id => ! this.addedProductIds.includes(id));

                    if (! ids.length) {
                        this.$refs.pickerModal.close();

                        return;
                    }

                    this.$axios.get("{{ route('admin.catalog.products.search') }}", {
                            params: { ids },
                        })
                        .then(response => {
                            this.$emit('onProductAdded', response.data.data ?? []);

                            this.$refs.pickerModal.close();
                        })
                        .catch(() => {});
                },
            },
        });
    </script>
@endPushOnce
