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

            <div
                class="grid gap-4 rounded bg-white p-4 box-shadow dark:bg-cherry-900"
                :class="structure.levels === 2 ? 'xl:grid-cols-3' : 'xl:grid-cols-2'"
            >
                <x-admin::catalog.families.variant-level-column
                    :title="trans('admin::app.catalog.families.edit.parent-product')"
                    :description="trans('admin::app.catalog.families.edit.parent-product-info')"
                    count="commonAttributes.length"
                    searching="searching.common"
                    search-model="search.common"
                    search-key="common"
                    list="commonList"
                    visible-count="visibleCommonCount"
                    :empty-title="trans('admin::app.catalog.families.edit.no-parent-attributes')"
                    :empty-description="trans('admin::app.catalog.families.edit.no-parent-attributes-info')"
                    selection="selected.common"
                    :primary-label="trans('admin::app.catalog.families.edit.move-to-child')"
                    primary-click="moveSelected('common', 'variant')"
                    :secondary-label="trans('admin::app.catalog.families.edit.move-to-sub-parent')"
                    secondary-click="moveSelected('common', 'sub_parent')"
                    secondary-if="structure.levels === 2"
                />

                <x-admin::catalog.families.variant-level-column
                    v-if="structure.levels === 2"
                    :title="trans('admin::app.catalog.families.edit.sub-parent-product')"
                    :description="trans('admin::app.catalog.families.edit.sub-parent-product-info')"
                    count="structure.placements.sub_parent.length"
                    count-class="bg-unopim-primary-muted text-unopim-primary dark:bg-cherry-800 dark:text-unopim-primary"
                    searching="searching.sub_parent"
                    search-model="search.sub_parent"
                    search-key="sub_parent"
                    list="subParentAttributes"
                    visible-count="visibleSubParentCount"
                    :empty-title="trans('admin::app.catalog.families.edit.no-sub-parent-attributes')"
                    :empty-description="trans('admin::app.catalog.families.edit.drop-or-search-info')"
                    selection="selected.sub_parent"
                    :primary-label="trans('admin::app.catalog.families.edit.move-to-parent')"
                    primary-click="moveSelected('sub_parent', 'common')"
                    :secondary-label="trans('admin::app.catalog.families.edit.move-to-child')"
                    secondary-click="moveSelected('sub_parent', 'variant')"
                    :removable="true"
                />

                <x-admin::catalog.families.variant-level-column
                    :title="trans('admin::app.catalog.families.edit.variant-child-product')"
                    :description="trans('admin::app.catalog.families.edit.variant-child-product-info')"
                    count="structure.placements.variant.length"
                    count-class="bg-amber-100 text-amber-700 dark:bg-cherry-800 dark:text-amber-300"
                    searching="searching.variant"
                    search-model="search.variant"
                    search-key="variant"
                    list="variantAttributes"
                    visible-count="visibleVariantCount"
                    :empty-title="trans('admin::app.catalog.families.edit.no-child-attributes')"
                    :empty-description="trans('admin::app.catalog.families.edit.drop-or-search-info')"
                    selection="selected.variant"
                    :primary-label="trans('admin::app.catalog.families.edit.move-to-parent')"
                    primary-click="moveSelected('variant', 'common')"
                    :secondary-label="trans('admin::app.catalog.families.edit.move-to-sub-parent')"
                    secondary-click="moveSelected('variant', 'sub_parent')"
                    secondary-if="structure.levels === 2"
                    :removable="true"
                />
            </div>

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
                                    @input="onDraftNameInput"
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
                    search: {
                        common: '',
                        sub_parent: '',
                        variant: '',
                    },
                    searching: {
                        common: false,
                        sub_parent: false,
                        variant: false,
                    },
                    isSaving: false,
                    selected: {
                        common: [],
                        sub_parent: [],
                        variant: [],
                    },
                    collapsedGroups: {
                        common: {},
                        sub_parent: {},
                        variant: {},
                    },
                    commonList: [],
                    subParentList: [],
                    variantList: [],
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

                visibleSubParentCount() {
                    return this.subParentList.filter(attribute => this.matchesSearch(attribute, 'sub_parent')).length;
                },

                visibleVariantCount() {
                    return this.variantList.filter(attribute => this.matchesSearch(attribute, 'variant')).length;
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

                onDraftNameInput() {
                    if (this.draft.codeEdited) {
                        return;
                    }

                    this.draft.code = this.slug(this.draft.name);
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
                    const available = {
                        common: new Set(this.commonList.map(attribute => attribute.code)),
                        sub_parent: new Set(this.subParentList.map(attribute => attribute.code)),
                        variant: new Set(this.variantList.map(attribute => attribute.code)),
                    };

                    this.selected.common = this.selected.common.filter(code => available.common.has(code));
                    this.selected.sub_parent = this.selected.sub_parent.filter(code => available.sub_parent.has(code));
                    this.selected.variant = this.selected.variant.filter(code => available.variant.has(code));
                },

                refreshLists() {
                    const byCode = Object.fromEntries(this.allAttributes.map(attribute => [attribute.code, attribute]));

                    this.commonList = this.sortAttributes(this.commonAttributes);
                    this.subParentList = this.sortAttributes(this.structure.placements.sub_parent.map(code => byCode[code]).filter(Boolean));
                    this.variantList = this.sortAttributes(this.structure.placements.variant.map(code => byCode[code]).filter(Boolean));
                    this.sanitizeSelection();
                },

                syncPlacements() {
                    this.emitVariantEvent('placements.sync.before');

                    this.structure.placements.sub_parent = this.subParentList.map(attribute => attribute.code);
                    this.structure.placements.variant = this.variantList.map(attribute => attribute.code);

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
                                sub_parent: structure.levels === 2 ? structure.placements.sub_parent : [],
                                variant: structure.placements.variant,
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
