@php
    $familyModel = $attributeFamily['family'] ?? null;

    $axisOptions = $familyModel
        ? $familyModel->getConfigurableAttributes()->map(fn ($attribute) => [
            'code'  => $attribute->code,
            'label' => $attribute->admin_name ?? $attribute->name ?? $attribute->code,
        ])->values()->toArray()
        : [];
@endphp

<v-variant-structure-list
    :axis-options='@json($axisOptions)'
    datagrid-url="{{ route('admin.catalog.families.variant-structures.index', [$familyModel?->id, 'datagrid' => 1]) }}"
    save-url="{{ route('admin.catalog.families.variant-structures.save', $familyModel?->id) }}"
    edit-url-template="{{ route('admin.catalog.families.variant-structures.edit', [$familyModel?->id, '__ID__']) }}"
>
    <x-admin::shimmer.datagrid />
</v-variant-structure-list>

@pushOnce('scripts')
    <script type="text/x-template" id="v-variant-structure-list-template">
        <x-admin::layouts.tab-content-panel
            :title="trans('admin::app.catalog.families.edit.variants')"
        >
            <x-slot:actions>
                <button
                    type="button"
                    class="primary-button"
                    @click="openVariantModal"
                >
                    <span class="icon-add text-lg"></span>
                    @lang('admin::app.catalog.families.edit.add-variant')
                </button>
            </x-slot>

            <div
                class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-cherry-900 dark:text-amber-200"
                v-if="! axisOptions.length"
            >
                @lang('admin::app.catalog.families.edit.no-axis-options')
            </div>

            <template v-else>
                <x-admin::datagrid
                    compact
                    ::src="datagridUrl"
                />
            </template>

            <x-admin::modal ref="variantSettingsModal" type="large">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.families.edit.add-variant')
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
        </x-admin::layouts.tab-content-panel>
    </script>

    <script type="module">
        app.component('v-variant-structure-list', {
            template: '#v-variant-structure-list-template',

            props: {
                axisOptions: { type: Array, default: () => [] },
                datagridUrl: { type: String, required: true },
                saveUrl: { type: String, required: true },
                editUrlTemplate: { type: String, required: true },
            },

            data() {
                return {
                    isSaving: false,
                    draft: this.emptyDraft(),
                };
            },

            methods: {
                emptyDraft() {
                    return {
                        id: null,
                        code: '',
                        name: '',
                        codeEdited: false,
                        levels: 1,
                        axes: {
                            level_1: this.axisOptions[0]?.code ? [this.axisOptions[0].code] : [],
                            level_2: [],
                        },
                        placements: {
                            common: [],
                            sub_parent: [],
                            variant: [],
                        },
                    };
                },

                openVariantModal() {
                    this.draft = this.emptyDraft();
                    this.$refs.variantSettingsModal.open();
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

                setDraftLevels(levels) {
                    this.draft.levels = levels;

                    if (levels === 1) {
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

                activeAxisCodes(structure) {
                    return structure.levels === 2
                        ? [...structure.axes.level_1, ...structure.axes.level_2]
                        : [...structure.axes.level_1];
                },

                validateDraft() {
                    if (! this.draft.name || ! this.draft.code) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.catalog.families.edit.name-code-required')",
                        });

                        return false;
                    }

                    const axes = this.activeAxisCodes(this.draft);

                    if (
                        ! this.draft.axes.level_1.length
                        || (this.draft.levels === 2 && ! this.draft.axes.level_2.length)
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

                saveDraft() {
                    if (! this.validateDraft()) {
                        return;
                    }

                    this.isSaving = true;

                    this.$axios.put(this.saveUrl, {
                        structure: {
                            id: null,
                            code: this.draft.code,
                            name: this.draft.name,
                            levels: this.draft.levels,
                            axes: {
                                level_1: this.draft.axes.level_1,
                                level_2: this.draft.levels === 2 ? this.draft.axes.level_2 : [],
                            },
                            placements: {
                                common: [],
                                sub_parent: [],
                                variant: [],
                            },
                        },
                    })
                        .then(({ data }) => {
                            this.$refs.variantSettingsModal.close();

                            if (data.data?.id) {
                                window.location.href = this.editUrlTemplate.replace('__ID__', data.data.id);
                            }
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || "@lang('admin::app.catalog.families.edit.unable-save-variant-structure')",
                            });
                        })
                        .finally(() => {
                            this.isSaving = false;
                        });
                },
            },
        });
    </script>
@endPushOnce
