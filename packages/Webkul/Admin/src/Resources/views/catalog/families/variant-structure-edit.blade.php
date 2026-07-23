@php
    $tabItems = [
        [
            'key'   => 'general',
            'url'   => route('admin.catalog.families.edit', $attributeFamily->id),
            'label' => 'admin::app.components.layouts.sidebar.general',
        ],
        [
            'key'   => 'variants',
            'url'   => route('admin.catalog.families.edit', [$attributeFamily->id, 'variants' => 1]),
            'label' => 'admin::app.catalog.families.edit.variants',
        ],
        [
            'key'   => 'completeness',
            'url'   => route('admin.catalog.families.edit', [$attributeFamily->id, 'completeness' => 1]),
            'label' => 'completeness::app.components.layouts.sidebar.completeness',
        ],
    ];
@endphp

<x-admin::layouts.with-history
    activeTab="variants"
    :historyId="$attributeFamily->id"
    :general-url="route('admin.catalog.families.edit', $attributeFamily->id)"
    :history-url="route('admin.catalog.families.edit', [$attributeFamily->id, 'history' => 1])"
    :tab-items="$tabItems"
>
    <x-slot:entityName>
        attributeFamily
    </x-slot>

    <x-slot:title>
        {{ $structure['name'] ?? $structure['code'] }}
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.catalog.families.edit.title')"
            :back-url="route('admin.catalog.families.edit', [$attributeFamily->id, 'variants' => 1])"
            :back-label="trans('admin::app.catalog.families.edit.back-to-variants')"
            :sticky="false"
        />
    </x-slot>

    {{-- The with-history layout renders the default slot only for the "general" tab.
         This editor page runs under the "variants" tab, so its content must live in
         the tabContents slot (rendered for every tab) or the page renders blank. --}}
    <x-slot:tabContents>
        <v-variant-structure-editor
            :axis-options='@json($axisOptions)'
            :groups='@json($variantGroups)'
            :initial-structure='@json($structure)'
            save-url="{{ route('admin.catalog.families.variant-structures.save', $attributeFamily->id) }}"
            back-url="{{ route('admin.catalog.families.edit', [$attributeFamily->id, 'variants' => 1]) }}"
        >
            <x-admin::shimmer.families.attributes-panel />
        </v-variant-structure-editor>
    </x-slot:tabContents>

@pushOnce('scripts')
    <script type="text/x-template" id="v-variant-structure-editor-template">
        <div class="grid gap-4">
            <div class="flex items-start justify-between gap-4 max-lg:flex-col">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate text-xl font-bold text-gray-800 dark:text-slate-50" v-text="structure.name || structure.code"></p>

                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-cherry-800 dark:text-gray-300" v-text="structure.code"></span>

                        <span
                            class="rounded-full bg-unopim-primary-muted px-2 py-0.5 text-xs font-medium text-unopim-primary dark:bg-cherry-800 dark:text-unopim-primary"
                            v-text="levelPath(structure.levels)"
                        >
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                        <span class="rounded-md bg-gray-50 px-2 py-1 text-gray-600 dark:bg-cherry-800 dark:text-gray-300">
                            L1: @{{ axisGroupLabel(structure.axes.level_1) }}
                        </span>

                        <span
                            class="rounded-md bg-gray-50 px-2 py-1 text-gray-600 dark:bg-cherry-800 dark:text-gray-300"
                            v-if="structure.levels === 2"
                        >
                            L2: @{{ axisGroupLabel(structure.axes.level_2) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        class="secondary-button"
                        @click="openSettingsModal"
                    >
                        <span class="icon-edit text-lg"></span>
                        @lang('admin::app.catalog.families.edit.variant-setup')
                    </button>

                    <button
                        type="button"
                        class="primary-button"
                        :class="isSaving ? 'cursor-not-allowed opacity-70' : ''"
                        :disabled="isSaving"
                        @click="save"
                    >
                        @lang('admin::app.catalog.families.edit.save-variant')
                    </button>
                </div>
            </div>

            <div class="grid gap-4 rounded bg-white p-4 box-shadow dark:bg-cherry-900 xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]"
                :class="structure.levels === 2 ? 'xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto_minmax(0,1fr)]' : ''"
            >
                {{-- Common pool: the only column that shows the full group tree. --}}
                <div class="min-w-0">
                    <x-admin::list.panel-header
                        :title="trans('admin::app.catalog.families.edit.parent-product')"
                        :description="trans('admin::app.catalog.families.edit.parent-product-info')"
                        searching="searching.common"
                    >
                        <x-admin::search.field
                            icon-position="left"
                            :placeholder="trans('admin::app.catalog.families.edit.search')"
                            v-model.trim="search.common"
                            clear-when="search.common"
                            clear-action="search.common = ''"
                        />
                    </x-admin::list.panel-header>

                    <div class="-mt-2 mb-3 flex min-h-[34px] items-center justify-between gap-3">
                        <span
                            class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-cherry-800 dark:text-gray-300"
                            v-text="commonList.length"
                        >
                        </span>

                        {{-- Bulk actions stay out of the way until something is ticked. --}}
                        <div class="flex flex-wrap justify-end gap-2" v-if="selected.common.length">
                            <button
                                type="button"
                                class="secondary-button !py-1.5 text-xs"
                                v-if="structure.levels === 2"
                                @click="moveSelected('common', 'sub_parent')"
                            >
                                @lang('admin::app.catalog.families.edit.add-to-level', ['number' => 1])
                            </button>

                            <button
                                type="button"
                                class="secondary-button !py-1.5 text-xs"
                                @click="moveSelected('common', 'variant')"
                            >
                                @{{ addToLeafLabel }}
                            </button>
                        </div>
                    </div>

                    <draggable
                        class="grid h-[calc(100vh-305px)] content-start gap-1.5 overflow-auto pb-4 ltr:pr-3 rtl:pl-3"
                        ghost-class="draggable-ghost"
                        handle=".icon-drag"
                        v-bind="{animation: 200}"
                        :list="commonList"
                        item-key="code"
                        group="variant-levels"
                        @change="syncPlacements"
                    >
                        <template #item="{ element }">
                            <div v-show="matchesSearch(element, 'common')">
                                <x-admin::catalog.families.variant-group-heading
                                    list="commonList"
                                    search-key="common"
                                />

                                <div v-show="! isVariantGroupCollapsed('common', element.groupCode)">
                                    <x-admin::catalog.families.variant-attribute-row selection="selected.common" />
                                </div>
                            </div>
                        </template>

                        <template #footer>
                            <x-admin::list.empty-state
                                v-if="! visibleCommonCount"
                                :title="trans('admin::app.catalog.families.edit.no-parent-attributes')"
                                :description="trans('admin::app.catalog.families.edit.no-parent-attributes-info')"
                            />
                        </template>
                    </draggable>
                </div>

                <div class="flex items-center justify-center text-gray-300 dark:text-cherry-800 max-xl:hidden">
                    <span class="icon-right text-2xl"></span>
                </div>

                <x-admin::catalog.families.variant-level-card
                    v-if="structure.levels === 2"
                    level="sub_parent"
                    number="1"
                    list="subParentAttributes"
                    axis-label="axisGroupLabel(structure.axes.level_1)"
                />

                <div
                    class="flex items-center justify-center text-gray-300 dark:text-cherry-800 max-xl:hidden"
                    v-if="structure.levels === 2"
                >
                    <span class="icon-right text-2xl"></span>
                </div>

                <x-admin::catalog.families.variant-level-card
                    level="variant"
                    number="structure.levels"
                    list="variantAttributes"
                    axis-label="leafAxisLabel"
                />
            </div>

            {{-- Add-attributes picker: the click-driven alternative to dragging. --}}
            <x-admin::modal ref="addAttributesModal" prevent-submit @close="cancelAddPicker">
                <x-slot:header>
                    <div>
                        <p class="text-lg font-bold text-gray-800 dark:text-white">
                            @{{ addPickerTitle }}
                        </p>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @lang('admin::app.catalog.families.edit.move-to-level-info')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content>
                    <x-admin::search.field
                        icon-position="left"
                        :placeholder="trans('admin::app.catalog.families.edit.search')"
                        v-model.trim="picker.query"
                        clear-when="picker.query"
                        clear-action="picker.query = ''"
                    />

                    <div class="mt-3 grid max-h-[22rem] content-start gap-1 overflow-auto">
                        <button
                            type="button"
                            class="grid grid-cols-[18px_minmax(0,1fr)_auto] items-center gap-2 rounded-md px-2 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-cherry-800"
                            v-for="attribute in pickerAttributes"
                            :key="attribute.code"
                            @click="togglePick(attribute.code)"
                        >
                            <span
                                class="text-2xl leading-none"
                                :class="picker.codes.includes(attribute.code) ? 'icon-checkbox-check text-unopim-primary' : 'icon-checkbox-normal text-gray-500'"
                            >
                            </span>

                            <span class="min-w-0 ltr:text-left rtl:text-right">
                                <span class="block truncate" v-text="attribute.label"></span>

                                <span class="block truncate text-[10px] uppercase tracking-wide text-gray-400" v-text="attribute.groupLabel"></span>
                            </span>

                            <span
                                class="shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500 dark:bg-cherry-800 dark:text-gray-300"
                                v-text="attribute.type"
                            >
                            </span>
                        </button>

                        <p v-if="! pickerAttributes.length" class="px-2 py-6 text-center text-xs text-gray-400">
                            @lang('admin::app.catalog.families.edit.no-parent-attributes')
                        </p>
                    </div>
                </x-slot>

                <x-slot:footer>
                    <div class="flex gap-2">
                        <button type="button" class="secondary-button" @click="cancelAddPicker">
                            @lang('admin::app.catalog.families.edit.cancel')
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="! picker.codes.length"
                            @click="confirmAddPicker"
                        >
                            @lang('admin::app.catalog.families.edit.add-attributes')
                        </button>
                    </div>
                </x-slot>
            </x-admin::modal>

            <x-admin::modal ref="variantSettingsModal" type="large">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.families.edit.variant-setup')
                    </p>
                </x-slot>

                <x-slot:content>
                    <div class="grid gap-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin::form.control-group class="mb-0">
                                <x-admin::form.control-group.label class="required font-medium">
                                    @lang('admin::app.catalog.families.edit.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="variant_structure_name"
                                    v-model="draft.name"
                                    placeholder="{{ trans('admin::app.catalog.families.edit.variant-name-placeholder') }}"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="mb-0">
                                <x-admin::form.control-group.label class="required font-medium">
                                    @lang('admin::app.catalog.families.edit.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="variant_structure_code"
                                    v-model="draft.code"
                                    placeholder="{{ trans('admin::app.catalog.families.edit.variant-code-placeholder') }}"
                                    @input="draft.codeEdited = true"
                                />
                            </x-admin::form.control-group>
                        </div>

                        <div>
                            <p class="mb-1.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::app.catalog.families.edit.structure')
                            </p>

                            <div class="inline-flex overflow-hidden rounded-md border dark:border-cherry-800">
                                <button
                                    type="button"
                                    class="px-3 py-2 text-sm"
                                    :class="draft.levels === 1 ? 'bg-unopim-primary-muted font-semibold text-unopim-primary dark:bg-cherry-800 dark:text-unopim-primary' : 'text-gray-600 dark:text-gray-300'"
                                    @click="setDraftLevels(1)"
                                >
                                    @lang('admin::app.catalog.families.edit.parent-child')
                                </button>

                                <button
                                    type="button"
                                    class="border-l px-3 py-2 text-sm dark:border-cherry-800"
                                    :class="draft.levels === 2 ? 'bg-unopim-primary-muted font-semibold text-unopim-primary dark:bg-cherry-800 dark:text-unopim-primary' : 'text-gray-600 dark:text-gray-300'"
                                    @click="setDraftLevels(2)"
                                >
                                    @lang('admin::app.catalog.families.edit.parent-sub-parent-child')
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin::form.control-group class="mb-0">
                                <x-admin::form.control-group.label class="required font-medium">
                                    @lang('admin::app.catalog.families.edit.level-1-attribute')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="multiselect"
                                    name="draft_axis_leaf"
                                    ::options="axisOptions"
                                    ::value="draftAxisValue('level_1')"
                                    track-by="code"
                                    label-by="label"
                                    @input="onDraftAxes('level_1', $event)"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group
                                class="mb-0"
                                v-if="draft.levels === 2"
                            >
                                <x-admin::form.control-group.label class="required font-medium">
                                    @lang('admin::app.catalog.families.edit.level-2-attribute')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="multiselect"
                                    name="draft_axis_sub"
                                    ::options="axisOptions"
                                    ::value="draftAxisValue('level_2')"
                                    track-by="code"
                                    label-by="label"
                                    @input="onDraftAxes('level_2', $event)"
                                />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </x-slot>

                <x-slot:footer>
                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            class="primary-button"
                            :class="isSaving ? 'cursor-not-allowed opacity-70' : ''"
                            :disabled="isSaving"
                            @click="saveDraft"
                        >
                            @lang('admin::app.catalog.families.edit.save-variant')
                        </button>
                    </div>
                </x-slot>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        const LEVEL_BADGE = @json(trans('admin::app.catalog.families.edit.level-badge', ['number' => ':number']));
        const ADD_TO_LEVEL = @json(trans('admin::app.catalog.families.edit.add-to-level', ['number' => ':number']));

        app.component('v-variant-structure-editor', {
            template: '#v-variant-structure-editor-template',

            props: {
                axisOptions: { type: Array, default: () => [] },
                groups: { type: Array, default: () => [] },
                initialStructure: { type: Object, required: true },
                saveUrl: { type: String, required: true },
                backUrl: { type: String, required: true },
            },

            data() {
                const structure = this.normalizeStructure(this.initialStructure);

                return {
                    structure,
                    draft: JSON.parse(JSON.stringify(structure)),
                    search: { common: '', sub_parent: '', variant: '' },
                    searching: { common: false },
                    isSaving: false,
                    selected: {
                        common: [],
                        sub_parent: [],
                        variant: [],
                    },
                    collapsedGroups: { common: {}, sub_parent: {}, variant: {} },
                    commonList: [],
                    subParentList: [],
                    variantList: [],
                    picker: { target: null, query: '', codes: [] },
                };
            },

            computed: {
                allAttributes() {
                    const byCode = {};

                    this.groups.forEach(group => {
                        (group.attributes || []).forEach(attribute => {
                            if (! byCode[attribute.code]) {
                                byCode[attribute.code] = {
                                    ...attribute,
                                    groupCode: group.code,
                                    groupLabel: group.label || group.code || 'Other',
                                };
                            }
                        });
                    });

                    return Object.values(byCode);
                },

                axisCodes() {
                    return this.activeAxisCodes(this.structure);
                },

                attributesByCode() {
                    const byCode = {};

                    this.allAttributes.forEach(attribute => {
                        byCode[attribute.code] = attribute;
                    });

                    return byCode;
                },

                // An axis belongs to the level it splits on: level_1 axes make the
                // sub parent, level_2 (or the only level) axes make the leaf.
                subParentAxisAttributes() {
                    if (this.structure.levels !== 2) {
                        return [];
                    }

                    return this.mapAxisAttributes(this.structure.axes.level_1);
                },

                variantAxisAttributes() {
                    return this.mapAxisAttributes(
                        this.structure.levels === 2 ? this.structure.axes.level_2 : this.structure.axes.level_1
                    );
                },


                commonAttributes() {
                    const assigned = new Set([
                        ...this.structure.placements.sub_parent,
                        ...this.structure.placements.variant,
                        ...this.axisCodes,
                    ]);

                    return this.allAttributes.filter(attribute => ! assigned.has(attribute.code));
                },

                visibleCommonCount() {
                    return this.commonList.filter(attribute => this.matchesSearch(attribute, 'common')).length;
                },

                leafAxisLabel() {
                    return this.axisGroupLabel(this.structure.levels === 2 ? this.structure.axes.level_2 : this.structure.axes.level_1);
                },

                addToLeafLabel() {
                    return this.addToLevelLabel(this.structure.levels);
                },

                addPickerTitle() {
                    if (! this.picker.target) {
                        return '';
                    }

                    return this.picker.target === 'sub_parent'
                        ? this.addToLevelLabel(1)
                        : this.addToLevelLabel(this.structure.levels);
                },

                pickerAttributes() {
                    const query = this.picker.query.trim().toLowerCase();

                    return this.commonList.filter(attribute => ! query
                        || String(attribute.label || attribute.code).toLowerCase().includes(query)
                        || String(attribute.groupLabel || '').toLowerCase().includes(query));
                },

                subParentAttributes() {
                    return this.subParentList;
                },

                variantAttributes() {
                    return this.variantList;
                },
            },

            created() {
                this.refreshLists();
            },

            watch: {
                'draft.name'(value) {
                    if (this.draft.codeEdited) {
                        return;
                    }

                    this.draft.code = this.slug(value);
                },
            },

            methods: {
                normalizeStructure(structure) {
                    return {
                        id: structure.id,
                        code: structure.code,
                        name: structure.name || structure.code,
                        codeEdited: Boolean(structure.code),
                        levels: Number(structure.levels || 1),
                        axes: this.normalizeAxes(structure.axes || []),
                        placements: {
                            common: structure.placements?.common || [],
                            sub_parent: structure.placements?.sub_parent || [],
                            variant: structure.placements?.variant || [],
                        },
                    };
                },

                normalizeAxes(axes) {
                    if (Array.isArray(axes)) {
                        return {
                            level_1: axes[0] ? [axes[0]] : [],
                            level_2: axes[1] ? [axes[1]] : [],
                        };
                    }

                    return {
                        level_1: Array.isArray(axes.level_1) ? axes.level_1 : [],
                        level_2: Array.isArray(axes.level_2) ? axes.level_2 : [],
                    };
                },

                emitVariantEvent(name, payload = {}) {
                    this.$emitter.emit(`catalog.family.variant-structure.${name}`, {
                        structure: this.structure,
                        draft: this.draft,
                        ...payload,
                    });
                },

                openSettingsModal() {
                    this.emitVariantEvent('setup.open.before');

                    this.draft = JSON.parse(JSON.stringify(this.structure));
                    this.$refs.variantSettingsModal.open();

                    this.emitVariantEvent('setup.open.after');
                },

                slug(value) {
                    return String(value || '')
                        .trim()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                },

                draftAxisValue(level) {
                    return JSON.stringify(this.draft.axes[level] || []);
                },

                onDraftAxes(level, value) {
                    try {
                        if (! value) {
                            this.draft.axes[level] = [];

                            return;
                        }

                        const options = JSON.parse(value);

                        this.draft.axes[level] = Array.isArray(options)
                            ? options.map(option => option.code)
                            : [];

                        this.fixDraftAxes();
                    } catch (e) {}
                },

                fixDraftAxes() {
                    if (this.draft.levels !== 2) {
                        return;
                    }

                    const levelOne = new Set(this.draft.axes.level_1);

                    this.draft.axes.level_2 = this.draft.axes.level_2.filter(code => ! levelOne.has(code));
                },

                levelBadge(number) {
                    return LEVEL_BADGE.replace(':number', number);
                },

                addToLevelLabel(number) {
                    return ADD_TO_LEVEL.replace(':number', number);
                },

                openAddPicker(target) {
                    this.picker = { target: target, query: '', codes: [] };

                    this.$nextTick(() => {
                        if (this.$refs.addAttributesModal) {
                            this.$refs.addAttributesModal.open();
                        }
                    });
                },

                togglePick(code) {
                    this.picker.codes = this.picker.codes.includes(code)
                        ? this.picker.codes.filter(item => item !== code)
                        : [...this.picker.codes, code];
                },

                cancelAddPicker() {
                    const target = this.picker.target;

                    this.picker = { target: null, query: '', codes: [] };

                    // close() flips isOpen before it emits, so this re-entry from @close stops here.
                    if (target && this.$refs.addAttributesModal && this.$refs.addAttributesModal.isOpen) {
                        this.$refs.addAttributesModal.close();
                    }
                },

                confirmAddPicker() {
                    const target = this.picker.target;
                    const codes = this.picker.codes;

                    if (! target || ! codes.length) {
                        return;
                    }

                    this.structure.placements[target] = [
                        ...this.structure.placements[target].filter(code => ! codes.includes(code)),
                        ...codes,
                    ];

                    const other = target === 'variant' ? 'sub_parent' : 'variant';

                    this.structure.placements[other] = this.structure.placements[other].filter(code => ! codes.includes(code));

                    this.selected.common = this.selected.common.filter(code => ! codes.includes(code));

                    this.refreshLists();

                    this.cancelAddPicker();
                },

                axisLabel(code) {
                    const option = this.axisOptions.find(axis => axis.code === code);

                    return option ? option.label : code;
                },

                axisGroupLabel(codes) {
                    const labels = (codes || []).map(code => this.axisLabel(code)).filter(Boolean);

                    return labels.length ? labels.join(' + ') : "@lang('admin::app.catalog.families.edit.no-axis-selected')";
                },

                levelPath(levels) {
                    return Number(levels) === 2
                        ? "@lang('admin::app.catalog.families.edit.parent-sub-parent-child')"
                        : "@lang('admin::app.catalog.families.edit.parent-child')";
                },

                mapAxisAttributes(codes) {
                    return (codes || [])
                        .map(code => this.attributesByCode[code])
                        .filter(Boolean)
                        .map(attribute => ({ ...attribute, locked: true }));
                },

                activeAxisCodes(structure) {
                    return structure.levels === 2
                        ? [...structure.axes.level_1, ...structure.axes.level_2]
                        : [...structure.axes.level_1];
                },

                setDraftLevels(levels) {
                    this.draft.levels = levels;

                    if (levels === 1) {
                        this.draft.placements.variant = [
                            ...this.draft.placements.variant,
                            ...this.draft.placements.sub_parent,
                        ];

                        this.draft.placements.sub_parent = [];
                        this.draft.axes.level_2 = [];

                        return;
                    }

                    if (! this.draft.axes.level_2.length) {
                        const used = new Set(this.draft.axes.level_1);
                        const fallback = this.axisOptions.find(option => ! used.has(option.code));

                        if (fallback) {
                            this.draft.axes.level_2 = [fallback.code];
                        }
                    }

                    this.fixDraftAxes();
                },

                matchesSearch(attribute, key) {
                    const term = String(this.search[key] || '').toLowerCase();

                    if (! term) {
                        return true;
                    }

                    return [attribute.label, attribute.code, attribute.type]
                        .filter(Boolean)
                        .some(value => String(value).toLowerCase().includes(term));
                },

                sortAttributes(attributes) {
                    return [...attributes].sort((first, second) => {
                        const groupCompare = String(first.groupLabel || '').localeCompare(String(second.groupLabel || ''));

                        if (groupCompare !== 0) {
                            return groupCompare;
                        }

                        return String(first.label || first.code).localeCompare(String(second.label || second.code));
                    });
                },

                isFirstInGroup(attribute, list, key) {
                    const visibleList = list.filter(item => this.matchesSearch(item, key));
                    const index = visibleList.findIndex(item => item.code === attribute.code);

                    if (index === -1) {
                        return false;
                    }

                    return index === 0 || visibleList[index - 1].groupCode !== attribute.groupCode;
                },

                groupVisibleCount(attribute, list, key) {
                    return list.filter(item => item.groupCode === attribute.groupCode && this.matchesSearch(item, key)).length;
                },

                toggleVariantGroup(key, groupCode) {
                    this.collapsedGroups[key] = {
                        ...this.collapsedGroups[key],
                        [groupCode]: ! this.collapsedGroups[key]?.[groupCode],
                    };
                },

                isVariantGroupCollapsed(key, groupCode) {
                    return Boolean(this.collapsedGroups[key]?.[groupCode]);
                },

                toggleSelected(path, code) {
                    const [, key] = path.split('.');
                    const selected = this.selected[key] || [];

                    this.selected[key] = selected.includes(code)
                        ? selected.filter(item => item !== code)
                        : [...selected, code];
                },

                sanitizeSelection() {
                    const selectable = list => new Set(list.filter(attribute => ! attribute.locked).map(attribute => attribute.code));

                    const available = {
                        common: selectable(this.commonList),
                        sub_parent: selectable(this.subParentList),
                        variant: selectable(this.variantList),
                    };

                    this.selected.common = this.selected.common.filter(code => available.common.has(code));
                    this.selected.sub_parent = this.selected.sub_parent.filter(code => available.sub_parent.has(code));
                    this.selected.variant = this.selected.variant.filter(code => available.variant.has(code));
                },

                refreshLists() {
                    const byCode = Object.fromEntries(this.allAttributes.map(attribute => [attribute.code, attribute]));

                    this.commonList = this.sortAttributes(this.commonAttributes);

                    this.subParentList = this.sortAttributes([
                        ...this.structure.placements.sub_parent.map(code => byCode[code]).filter(Boolean),
                        ...this.subParentAxisAttributes,
                    ]);

                    this.variantList = this.sortAttributes([
                        ...this.structure.placements.variant.map(code => byCode[code]).filter(Boolean),
                        ...this.variantAxisAttributes,
                    ]);

                    this.sanitizeSelection();
                },

                syncPlacements() {
                    this.emitVariantEvent('placements.sync.before');

                    this.structure.placements.sub_parent = this.subParentList.filter(attribute => ! attribute.locked).map(attribute => attribute.code);
                    this.structure.placements.variant = this.variantList.filter(attribute => ! attribute.locked).map(attribute => attribute.code);

                    const subCodes = new Set(this.structure.placements.sub_parent);
                    const variantCodes = new Set(this.structure.placements.variant);

                    this.structure.placements.sub_parent = this.structure.placements.sub_parent.filter(code => ! variantCodes.has(code));
                    this.structure.placements.variant = this.structure.placements.variant.filter(code => ! subCodes.has(code));

                    this.refreshLists();

                    this.emitVariantEvent('placements.sync.after');
                },

                moveToCommon(code) {
                    this.emitVariantEvent('attribute.move.before', {
                        from: 'assigned',
                        to: 'common',
                        codes: [code],
                    });

                    this.structure.placements.sub_parent = this.structure.placements.sub_parent.filter(item => item !== code);
                    this.structure.placements.variant = this.structure.placements.variant.filter(item => item !== code);
                    this.refreshLists();

                    this.emitVariantEvent('attribute.move.after', {
                        from: 'assigned',
                        to: 'common',
                        codes: [code],
                    });
                },

                moveSelected(from, to) {
                    const selectedCodes = [...new Set(this.selected[from] || [])];

                    if (! selectedCodes.length) {
                        return;
                    }

                    this.emitVariantEvent('attributes.move.before', {
                        from,
                        to,
                        codes: selectedCodes,
                    });

                    this.structure.placements.sub_parent = this.structure.placements.sub_parent.filter(code => ! selectedCodes.includes(code));
                    this.structure.placements.variant = this.structure.placements.variant.filter(code => ! selectedCodes.includes(code));

                    if (to === 'sub_parent' && this.structure.levels === 2) {
                        this.structure.placements.sub_parent = [
                            ...this.structure.placements.sub_parent,
                            ...selectedCodes,
                        ];
                    }

                    if (to === 'variant') {
                        this.structure.placements.variant = [
                            ...this.structure.placements.variant,
                            ...selectedCodes,
                        ];
                    }

                    this.selected[from] = [];
                    this.refreshLists();

                    this.emitVariantEvent('attributes.move.after', {
                        from,
                        to,
                        codes: selectedCodes,
                    });
                },

                validateStructure(structure) {
                    if (! structure.name || ! structure.code) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.catalog.families.edit.name-code-required')",
                        });

                        return false;
                    }

                    const axes = this.activeAxisCodes(structure);

                    if (
                        ! structure.axes.level_1.length
                        || (structure.levels === 2 && ! structure.axes.level_2.length)
                        || new Set(axes).size !== axes.length
                    ) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.catalog.families.edit.select-axis-warning')",
                        });

                        return false;
                    }

                    return true;
                },

                structurePayload(structure) {
                    const axes = this.activeAxisCodes(structure);
                    const assigned = new Set([
                        ...structure.placements.sub_parent,
                        ...structure.placements.variant,
                        ...axes,
                    ]);

                    return {
                        structure: {
                            id: structure.id,
                            code: structure.code,
                            name: structure.name,
                            levels: structure.levels,
                            axes: {
                                level_1: structure.axes.level_1,
                                level_2: structure.levels === 2 ? structure.axes.level_2 : [],
                            },
                            placements: {
                                common: this.allAttributes
                                    .filter(attribute => ! assigned.has(attribute.code))
                                    .map(attribute => attribute.code),
                                // A single-level structure has no sub parent: fold anything
                                // sitting there into the leaf rather than dropping it.
                                sub_parent: structure.levels === 2 ? structure.placements.sub_parent : [],
                                variant: structure.levels === 2
                                    ? structure.placements.variant
                                    : [...new Set([...structure.placements.variant, ...structure.placements.sub_parent])],
                            },
                        },
                    };
                },

                saveDraft() {
                    if (! this.validateStructure(this.draft)) {
                        return;
                    }

                    this.emitVariantEvent('setup.save.before');

                    this.saveStructure(this.draft).then(savedStructure => {
                        if (! savedStructure) {
                            this.emitVariantEvent('setup.save.error');

                            return;
                        }

                        this.$refs.variantSettingsModal.close();

                        this.emitVariantEvent('setup.save.after', {
                            savedStructure,
                        });
                    });
                },

                save() {
                    this.emitVariantEvent('save.before');

                    this.syncPlacements();

                    if (! this.validateStructure(this.structure)) {
                        this.emitVariantEvent('save.error');

                        return Promise.resolve(null);
                    }

                    return this.saveStructure(this.structure).then(savedStructure => {
                        if (! savedStructure) {
                            this.emitVariantEvent('save.error');

                            return savedStructure;
                        }

                        this.emitVariantEvent('save.after', {
                            savedStructure,
                        });

                        if (savedStructure) {
                            window.location.href = this.backUrl;
                        }

                        return savedStructure;
                    });
                },

                saveStructure(structure) {
                    this.emitVariantEvent('persist.before', {
                        payload: this.structurePayload(structure),
                    });

                    this.isSaving = true;

                    return this.$axios.put(this.saveUrl, this.structurePayload(structure))
                        .then(({ data }) => {
                            this.structure = this.normalizeStructure(data.data);
                            this.draft = JSON.parse(JSON.stringify(this.structure));
                            this.refreshLists();

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: data.message || "@lang('admin::app.catalog.families.edit.variant-saved')",
                            });

                            this.emitVariantEvent('persist.after', {
                                response: data,
                            });

                            return this.structure;
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || "@lang('admin::app.catalog.families.edit.unable-save-variant-structure')",
                            });

                            this.emitVariantEvent('persist.error', {
                                error,
                            });

                            return null;
                        })
                        .finally(() => {
                            this.isSaving = false;
                        });
                },
            },
        });
    </script>
@endPushOnce
</x-admin::layouts.with-history>
