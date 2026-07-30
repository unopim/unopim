@php
    $requestedTab = match (true) {
        request()->has('history')      => 'history',
        request()->has('variants')     => 'variants',
        request()->has('completeness') => 'completeness',
        default                        => 'general',
    };

    $historyQuery = array_diff_key(request()->query(), array_flip(['variants', 'completeness', 'history']));

    $familyHistoryUrl = request()->url().'?'.http_build_query($historyQuery + ['history' => 1]);

    $tabItems = array_values(array_filter([
        [
            'key'        => 'general',
            'url'        => '?',
            'label'      => 'admin::app.components.layouts.sidebar.general',
            'permission' => 'catalog.families.edit',
        ],
        [
            'key'        => 'variants',
            'url'        => '?variants',
            'label'      => 'admin::app.catalog.families.edit.variants',
            'permission' => 'catalog.families.variant-structures',
        ],
        [
            'key'        => 'completeness',
            'url'        => '?completeness',
            'label'      => 'completeness::app.components.layouts.sidebar.completeness',
            'permission' => 'catalog.families.completeness',
        ],
    ], fn ($tab) => bouncer()->hasPermission($tab['permission'])));

    $permittedTabs = array_column($tabItems, 'key');

    abort_if(empty($permittedTabs), 403, 'This action is unauthorized');

    $activeTab = in_array($requestedTab, [...$permittedTabs, 'history'], true)
        ? $requestedTab
        : $permittedTabs[0];
@endphp

<x-admin::layouts.with-history
    :activeTab="$activeTab"
    :tab-items="$tabItems"
    :history-url="$familyHistoryUrl"
>
    <x-slot:entityName>
        attributeFamily
    </x-slot>
    <x-slot:title>
        @lang('admin::app.catalog.families.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.catalog.families.edit.title')"
            :back-url="route('admin.catalog.families.index')"
            :back-label="trans('admin::app.catalog.families.edit.back-btn')"
            :sticky="false"
        />
    </x-slot>

    <x-slot:tabContents>
        @switch($activeTab)
            @case('general')
                <x-admin::form
                    id="attribute-family-edit-form"
                    ajax
                    method="PUT"
                    :action="route('admin.catalog.families.update', $attributeFamily['family']->id)"
                >

                    {!! view_render_event('unopim.admin.catalog.families.edit.edit_form_control.before', ['attributeFamily' => $attributeFamily]) !!}

                    <div class="flex gap-2.5 mt-3.5">

                        {!! view_render_event('unopim.admin.catalog.families.edit.card.attributes-panel.before', ['attributeFamily' => $attributeFamily]) !!}

                        <div class="flex flex-col gap-2 flex-1 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <v-family-attributes>
                                <x-admin::shimmer.families.attributes-panel />
                            </v-family-attributes>
                        </div>

                        {!! view_render_event('unopim.admin.catalog.families.edit.card.attributes-panel.after', ['attributeFamily' => $attributeFamily]) !!}

                        {!! view_render_event('unopim.admin.catalog.families.edit.card.accordion.general.before', ['attributeFamily' => $attributeFamily]) !!}
                    
                        <div class="flex flex-col gap-2 w-[360px] max-w-full select-none">
                            <div class="relative p-[16px] bg-white dark:bg-cherry-800 rounded-[4px] box-shadow">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.general')
                                </p>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required !text-gray-800 dark:!text-white">
                                        @lang('admin::app.catalog.families.edit.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        value="{{ old('code') ?? $attributeFamily['family']->code }}"
                                        disabled="disabled"
                                        :label="trans('admin::app.catalog.families.edit.code')"
                                        :placeholder="trans('admin::app.catalog.families.edit.enter-code')"
                                    />
                                    <input type="hidden" name="code" value="{{ $attributeFamily['family']->code }}"/>
                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>
                            </div>

                            <div class="relative p-[16px] bg-white dark:bg-cherry-800 rounded-[4px] box-shadow">
                                <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.label')
                                </p>

                                <x-admin::form.translatable-field
                                    :locales="$locales"
                                    :values="collect($locales)->mapWithKeys(fn ($locale) => [$locale->code => old($locale->code)['name'] ?? ($attributeFamily['family']->translate($locale->code)->name ?? '')])->all()"
                                    :label="trans('admin::app.catalog.families.edit.name')"
                                    :placeholder="trans('admin::app.catalog.families.edit.enter-name')"
                                />
                            </div>
                        </div>

                        {!! view_render_event('unopim.admin.catalog.families.edit.card.accordion.general.after', ['attributeFamily' => $attributeFamily]) !!}
                    </div>

                    {!! view_render_event('unopim.admin.catalog.families.edit.edit_form_control.after', ['attributeFamily' => $attributeFamily]) !!}

                </x-admin::form>

                @break

            @case('variants')
                @include('admin::catalog.families.edit.variants', [
                    'attributeFamily' => $attributeFamily,
                ])

                @break

            @case('completeness')
                @include('admin::catalog.families.completeness.index', [
                    'familyId'    => $attributeFamilyId,
                    'allChannels' => $allChannels,
                ])

                @break
        @endswitch
    </x-slot>
    @if ($activeTab === 'general')
        @pushOnce('scripts')
            <script
                type="text/x-template"
                id="v-family-attributes-template"
            >
                <div>
                    <input
                        type="hidden"
                        name="_attribute_groups_dirty"
                        :value="dirtyTick"
                        data-attribute-groups-dirty
                    />

                    <div class="flex flex-wrap gap-2.5 justify-between mb-2.5 p-4">
                        <div class="flex flex-col gap-2">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.catalog.families.edit.attribute-groups')
                            </p>

                            <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                                @lang('admin::app.catalog.families.edit.groups-info')
                            </p>
                        </div>
                        
                        <div class="flex gap-x-1 items-center">
                            <div
                                class="secondary-button"
                                @click="$refs.assignGroupModal.open()"
                            >
                                @lang('admin::app.catalog.families.edit.assign-group-btn')
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-2.5 p-4">
                        <div class="">
                            <x-admin::list.panel-header
                                :title="trans('admin::app.catalog.families.edit.main-column')"
                                :description="trans('admin::app.catalog.families.edit.main-column-info')"
                                searching="isSearchingAssigned"
                            >
                                <x-admin::search.field
                                    icon-position="left"
                                    :placeholder="trans('admin::app.catalog.families.edit.search')"
                                    v-model.trim="assignedSearchTerm"
                                    v-debounce="500"
                                    v-focus
                                    @change="searchGroups(assignedSearchTerm)"
                                    @keydown.enter.prevent="searchGroups(assignedSearchTerm)"
                                    clear-when="assignedSearchTerm"
                                    clear-action="assignedSearchTerm = ''; searchGroups('')"
                                />
                            </x-admin::list.panel-header>

                            <div
                                v-if="! defaultFamilyGroups.length"
                                class="h-[calc(100vh-285px)] overflow-auto pb-4 ltr:border-r ltr:pr-4 rtl:border-l rtl:pl-4 border-gray-200"
                            >
                                <x-admin::list.empty-state
                                    class="min-h-[180px]"
                                    icon="icon-folder"
                                    :title="trans('admin::app.catalog.families.edit.no-assigned-groups')"
                                    :description="trans('admin::app.catalog.families.edit.no-assigned-groups-info')"
                                />
                            </div>

                            <draggable
                                v-else
                                id="assigned-attribute-groups"
                                class="h-[calc(100vh-285px)] pb-[16px] overflow-auto ltr:border-r rtl:border-l border-gray-200"
                                ghost-class="draggable-ghost"
                                handle=".icon-drag"
                                v-bind="{animation: 200}"
                                :list="visibleFamilyGroups"
                                item-key="id"
                                group="groups"
                                :disabled="Boolean(assignedSearchTerm)"
                            >
                                <template #item="{ element, index }">
                                    <div class="ltr:pr-3 rtl:pl-3">
                                            <x-admin::catalog.families.group-row
                                                :remove-title="trans('admin::app.catalog.families.edit.remove-group-btn')"
                                            />

                                        <div
                                            class="relative ltr:ml-[70px] rtl:mr-[70px]"
                                            v-show="! element.hide"
                                        >
                                            <div class="absolute bottom-3 top-0 w-px bg-gray-200 dark:bg-cherry-800 ltr:left-0 rtl:right-0"></div>

                                            <draggable
                                                class="min-h-8 py-1 ltr:pl-5 rtl:pr-5"
                                                ghost-class="draggable-ghost"
                                                handle=".icon-drag"
                                                v-bind="{animation: 200}"
                                                :list="getVisibleGroupAttributes(element)"
                                                item-key="id"
                                                group="attributes"
                                                :move="onAttributeMove"
                                                @change="onChange($event, element)"
                                            >
                                                <template #item="{ element, index }">
                                                    <x-admin::catalog.families.assigned-attribute-row />
                                                </template>
                                            </draggable>

                                            <x-admin::catalog.families.drop-attributes-placeholder v-if="! element.customAttributes.length" />
                                        </div>
                                    </div>
                                </template>
                            </draggable>

                            <input
                                type="hidden"
                                name="retained_group_mappings"
                                :value="retainedGroupMappings.join(',')"
                            />

                            <x-admin::pagination.compact
                                class="mt-3"
                                current-page="groupsPage"
                                total-pages="groupsLastPage"
                                change="changeGroupPage"
                            />
                        </div>

                        <div>
                            <x-admin::list.panel-header
                                :title="trans('admin::app.catalog.families.edit.unassigned-attributes')"
                                :description="trans('admin::app.catalog.families.edit.unassigned-attributes-info')"
                                searching="isSearching"
                            >
                                <x-admin::search.field
                                    icon-position="left"
                                    :placeholder="trans('admin::app.catalog.families.edit.search')"
                                    v-model.trim="searchTerm"
                                    v-debounce="500"
                                    v-focus
                                    @change="search(searchTerm)"
                                    @keydown.enter.prevent="search(searchTerm)"
                                    clear-when="searchTerm"
                                    clear-action="searchTerm = ''; search('')"
                                />
                            </x-admin::list.panel-header>

                            <template v-if="isLoading">
                                <div v-if="isLoading" class="grid gap-y-2.5 pt-3 h-[calc(100vh-285px)] pb-[16px] pt-3 overflow-auto ">
                                    <div v-for="n in 35" :key="n" class="shimmer w-[302px] h-[38px] rounded-md"></div>
                                </div>
                                <div class="flex gap-1 items-left justify-right mt-2.5">
                                    <div class="shimmer w-[38px] h-[38px] rounded-md"></div>
                                    <div class="shimmer w-[38px] h-[38px] rounded-md"></div>
                                    <div class="shimmer w-[60px] h-[38px] rounded-md"></div>
                                    <div class="shimmer w-[38px] h-[38px] rounded-md"></div>
                                </div>
                            </template>

                            <template v-else>
                                <div
                                    class="flex items-center justify-between gap-3 mb-2 text-xs text-gray-500"
                                    v-if="customAttributes.length"
                                >
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="text-2xl rounded-md cursor-pointer"
                                            :class="pageAllSelected ? 'icon-checkbox-check text-unopim-primary' : 'icon-checkbox-normal text-gray-500'"
                                            @click="selectPage(! pageAllSelected)"
                                        >
                                        </button>

                                        <span>
                                            @lang('admin::app.catalog.families.edit.select-page')
                                            (@{{ customAttributes.length }} @lang('admin::app.catalog.families.edit.shown') / @{{ formattedTotalAttributes }})
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        class="text-unopim-primary font-medium hover:underline"
                                        v-if="selectedAttrs.length"
                                        @click="clearSelectedAttrs"
                                    >
                                        @lang('admin::app.catalog.families.edit.clear')
                                    </button>
                                </div>

                                <div
                                    v-if="canSelectAllMatching || selectAllAcrossPages"
                                    class="flex items-center justify-center gap-1.5 mb-2 rounded-md bg-unopim-primary-soft/50 dark:bg-cherry-900 px-3 py-1.5 text-xs text-gray-600 dark:text-gray-300"
                                >
                                    <span v-if="selectAllAcrossPages">@{{ allSelectedLabel }}</span>

                                    <button
                                        v-else
                                        type="button"
                                        class="text-unopim-primary font-medium hover:underline disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isSelectingAll"
                                        @click="selectAllMatching"
                                    >
                                        @{{ selectAllMatchingLabel }}
                                    </button>
                                </div>

                                <x-admin::catalog.families.bulk-assign
                                    :select-group-placeholder="trans('admin::app.catalog.families.edit.select-destination-group')"
                                />

                                <draggable
                                    id="unassigned-attributes"
                                    class="h-[calc(100vh-285px)] pb-4 overflow-auto"
                                    ghost-class="draggable-ghost"
                                    handle=".icon-drag"
                                    v-bind="{animation: 200}"
                                    :list="customAttributes"
                                    item-key="id"
                                    group="attributes"
                                    :move="onAttributeMove"
                                    @change="onUnassignedChange"
                                >
                                    <template #item="{ element, index }">
                                        <x-admin::catalog.families.unassigned-attribute-row />
                                    </template>

                                    <template #footer>
                                        <x-admin::list.empty-state
                                            v-if="! customAttributes.length"
                                            class="bg-gray-50 dark:bg-cherry-900"
                                            :title="trans('admin::app.catalog.families.edit.no-unassigned-attributes')"
                                            :description="trans('admin::app.catalog.families.edit.no-unassigned-attributes-info')"
                                        />
                                    </template>
                                </draggable>

                                <x-admin::pagination.compact
                                    class="mt-3"
                                    current-page="currentPage"
                                    total-pages="totalPages"
                                    change="changePage"
                                />
                            </template>

                        </div>
                    </div>

                    <x-admin::form
                        v-slot="{ meta, errors, handleSubmit }"
                        as="div"
                    >
                        <form @submit.stop="handleSubmit($event, assignGroup)">
                            <x-admin::modal ref="assignGroupModal">
                                <x-slot:header>
                                    <p class="text-lg text-gray-800 dark:text-white font-bold">
                                        @lang('admin::app.catalog.families.edit.assign-group-title')
                                    </p>
                                </x-slot>

                                <x-slot:content>
                                    <x-admin::form.control-group class="mb-4">
                                        <x-admin::form.control-group.label class="required font-medium">
                                            @lang('admin::app.catalog.families.edit.groups')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="multiselect"
                                            name="group"
                                            rules="required"
                                            :label="trans('admin::app.catalog.families.edit.groups')"
                                            async="true"
                                            entity-name="attribute_group"
                                            track-by="id"
                                            label-by="label"
                                            ::query-params="assignedGroupExcludeParams"
                                            taggable="true"
                                            :tag-placeholder="trans('admin::app.catalog.families.edit.create-group')"
                                            :create-route="route('admin.catalog.attribute.groups.quick-store')"
                                        />

                                        <x-admin::form.control-group.error control-name="group" /> 
                                    </x-admin::form.control-group>
                                </x-slot>

                                <x-slot:footer>
                                    <div class="flex gap-x-2.5 items-center">
                                        <button 
                                            type="submit"
                                            class="primary-button"
                                        >
                                            @lang('admin::app.catalog.families.edit.assign-group-btn')
                                        </button>
                                    </div>
                                </x-slot>
                            </x-admin::modal>
                        </form>
                    </x-admin::form>
                </div>
            </script>

            <script type="module">
                app.component('v-family-attributes', {
                    template: '#v-family-attributes-template',

                    data: function () {
                        return {
                            isLoading: false,
                            currentPage: 1,
                            totalPages: 2,
                            totalAttributes: 0,
                            serverTotalAttributes: 0,
                            pendingAssignedCodes: [],
                            isSearching: false,
                            isSearchingAssigned: false,
                            selectedGroup: {
                                id: null,
                                code: null,
                                name: null,
                            },
                            getAttributeRoute: "{{ route('admin.catalog.options.fetch-all')}}",
                            groupAttributesRoute: "{{ route('admin.catalog.families.group-attributes', ['id' => $attributeFamily['family']->id ?? 0, 'groupId' => '__group_id__']) }}",
                            groupsRoute: "{{ route('admin.catalog.families.groups', ['id' => $attributeFamily['family']->id ?? 0]) }}",
                            familyId: {{ $attributeFamily['family']->id ?? 0 }},
                            groupsPage: 1,
                            groupsLastPage: {{ $attributeFamily['groupsLastPage'] ?? 1 }},
                            groupsPerPage: {{ $attributeFamily['groupsPerPage'] ?? 25 }},
                            retainedGroupMappings: @json($attributeFamily['groupMappingIds'] ?? []),
                            customAttributes: [],
                            familyDefaultGroups: @json($attributeFamily['familyGroupMappings']),
                            initialFamilyGroups: @json($attributeFamily['familyGroupMappings']),
                            dropReverted: false,
                            searchTerm: '',
                            assignedSearchTerm: '',
                            params: {},
                            selectedAttrs: [],
                            selectedAttrDetails: {},
                            allMatchingAttributes: [],
                            selectAllAcrossPages: false,
                            isSelectingAll: false,
                            bulkGroup: null,
                            dirtyTick: 0,
                            selectAllMatchingText: @json(trans('admin::app.catalog.families.edit.select-all-matching')),
                            allSelectedText: @json(trans('admin::app.catalog.families.edit.all-selected')),
                        }
                    },

                    computed: {
                        defaultFamilyGroups() {
                            return this.familyDefaultGroups;
                        },

                        visibleFamilyGroups() {
                            return this.familyDefaultGroups;
                        },

                        groupPositionOffset() {
                            return (this.groupsPage - 1) * this.groupsPerPage;
                        },

                        pageAllSelected() {
                            return this.customAttributes.length > 0
                                && this.customAttributes.every(a => this.selectedAttrs.includes(this.attributeCode(a)));
                        },

                        bulkGroupOptions() {
                            return this.familyDefaultGroups.map(group => ({
                                code: group.code,
                                label: group.label || group.name || group.code,
                            }));
                        },

                        bulkGroupValue() {
                            const option = this.bulkGroupOptions.find(o => o.code === this.bulkGroup);

                            return option ? JSON.stringify(option) : '';
                        },

                        bulkGroupName() {
                            const option = this.bulkGroupOptions.find(o => o.code === this.bulkGroup);

                            return option ? option.label : '';
                        },

                        canSelectAllMatching() {
                            return this.pageAllSelected
                                && ! this.selectAllAcrossPages
                                && this.totalAttributes > this.customAttributes.length;
                        },

                        selectAllMatchingLabel() {
                            return this.selectAllMatchingText.replace(':total', this.formattedTotalAttributes);
                        },

                        allSelectedLabel() {
                            return this.allSelectedText.replace(':total', this.formattedTotalAttributes);
                        },

                        formattedTotalAttributes() {
                            return new Intl.NumberFormat().format(this.totalAttributes);
                        },

                        assignedGroupExcludeParams() {
                            return {
                                notInFamily: this.familyId,
                            };
                        }
                    },

                    mounted() {
                        this.getAttributes();

                        const firstGroup = this.familyDefaultGroups[0];

                        if (firstGroup) {
                            this.toggleGroup(firstGroup);
                        }

                        this.$emitter.on('unsaved-changes:reset', this.restoreFamilyGroups);
                    },

                    beforeUnmount() {
                        this.$emitter.off('unsaved-changes:reset', this.restoreFamilyGroups);
                    },

                    methods: {
                        restoreFamilyGroups() {
                            this.familyDefaultGroups = JSON.parse(JSON.stringify(this.initialFamilyGroups));

                            this.selectedAttrs = [];
                            this.selectAllAcrossPages = false;
                            this.allMatchingAttributes = [];
                            this.bulkGroup = null;
                            this.dirtyTick = 0;
                            this.pendingAssignedCodes = [];

                            this.getAttributes();
                        },

                        onMove: function(e) {
                            if (
                                e.to.id === 'unassigned-attributes'
                            ) {
                                this.dropReverted = true;

                                return false;
                            } else {
                                this.dropReverted = false;
                            }
                        },
                        
                        onEnd: function(e) {
                            if (this.dropReverted) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.removal-not-possible')" });
                            }

                            this.signalUnsaved();
                        },

                        isSkuAttribute(attribute) {
                            return this.attributeCode(attribute) === 'sku';
                        },

                        onAttributeMove(e) {
                            if (e.to.id === 'unassigned-attributes' && this.isSkuAttribute(e.draggedContext.element)) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.removal-not-possible')" });

                                return false;
                            }

                            return true;
                        },

                        /** Assignments made on this page but not saved yet; the unassigned query still counts them. */
                        markPendingAssignments(codes) {
                            codes.filter(Boolean).forEach(code => {
                                if (! this.pendingAssignedCodes.includes(code)) {
                                    this.pendingAssignedCodes.push(code);
                                }
                            });

                            this.totalAttributes = Math.max(0, this.serverTotalAttributes - this.pendingAssignedCodes.length);
                        },

                        releasePendingAssignment(code) {
                            this.pendingAssignedCodes = this.pendingAssignedCodes.filter(pending => pending !== code);

                            this.totalAttributes = Math.max(0, this.serverTotalAttributes - this.pendingAssignedCodes.length);
                        },

                        /** Assigned codes, including drags not saved yet — the query can only exclude saved ones. */
                        assignedAttributeCodes() {
                            const codes = new Set();

                            this.familyDefaultGroups.forEach(group => {
                                (group.customAttributes ?? []).forEach(attribute => {
                                    const code = this.attributeCode(attribute);

                                    if (code) {
                                        codes.add(code);
                                    }
                                });
                            });

                            return codes;
                        },

                        /**
                         * Dedupes within one group only: an attribute may legitimately
                         * be assigned to several groups of the same family.
                         */
                        dropDuplicateInGroup(group, attribute) {
                            const code = this.attributeCode(attribute);
                            let kept = false;

                            group.customAttributes = group.customAttributes.filter(candidate => {
                                if (this.attributeCode(candidate) !== code) {
                                    return true;
                                }

                                if (kept) {
                                    return false;
                                }

                                kept = true;

                                return true;
                            });
                        },

                        getGroupAttributes(group) {
                            const groupId = this.groupFormId(group);

                            group.customAttributes.forEach((attribute, index) => {
                                attribute.group_id = groupId;
                            });

                            return group.customAttributes;
                        },

                        groupFormId(group) {
                            const id = group?.id ?? group?.value;

                            return id && id !== 'undefined' ? id : null;
                        },

                        groupAttributesCount(group) {
                            return group.attributesLoaded === false
                                ? (group.attributesCount ?? 0)
                                : group.customAttributes.length;
                        },

                        fetchGroups() {
                            this.isSearchingAssigned = true;

                            return this.$axios
                                .get(this.groupsRoute, {
                                    params: {
                                        page:  this.groupsPage,
                                        query: this.assignedSearchTerm,
                                    },
                                })
                                .then(response => {
                                    this.familyDefaultGroups = response.data.groups || [];
                                    this.groupsLastPage = response.data.lastPage || 1;

                                    const firstGroup = this.familyDefaultGroups[0];

                                    if (firstGroup) {
                                        this.toggleGroup(firstGroup);
                                    }
                                })
                                .finally(() => {
                                    this.isSearchingAssigned = false;
                                });
                        },

                        changeGroupPage(page) {
                            if (page > 0 && page <= this.groupsLastPage) {
                                this.groupsPage = page;

                                this.fetchGroups();
                            }
                        },

                        searchGroups(term) {
                            this.assignedSearchTerm = term;
                            this.groupsPage = 1;

                            this.fetchGroups();
                        },

                        toggleGroup(group) {
                            group.hide = ! group.hide;

                            if (! group.hide) {
                                this.loadGroupAttributes(group);
                            }
                        },

                        loadGroupAttributes(group) {
                            const groupId = this.groupFormId(group);

                            if (! groupId || group.attributesLoaded !== false) {
                                return Promise.resolve(group);
                            }

                            if (! group.attributesRequest) {
                                group.attributesRequest = this.$axios
                                    .get(this.groupAttributesRoute.replace('__group_id__', groupId))
                                    .then(response => {
                                        group.customAttributes = response.data.attributes || [];
                                        group.attributesLoaded = true;

                                        return group;
                                    })
                                    .finally(() => {
                                        group.attributesRequest = null;
                                    });
                            }

                            return group.attributesRequest;
                        },

                        getVisibleGroupAttributes(group) {
                            const attributes = this.getGroupAttributes(group);

                            if (! this.assignedSearchTerm || this.matchesSearch(group, this.assignedSearchTerm.toLowerCase())) {
                                return attributes;
                            }

                            const term = this.assignedSearchTerm.toLowerCase();

                            return attributes.filter(attribute => this.matchesSearch(attribute, term));
                        },

                        attributeCode(attribute) {
                            return attribute.code || attribute.value;
                        },

                        matchesSearch(item, term) {
                            return [item.label, item.name, item.code]
                                .filter(Boolean)
                                .some(value => String(value).toLowerCase().includes(term));
                        },

                        toggleAttr(code) {
                            this.selectAllAcrossPages = false;
                            this.allMatchingAttributes = [];

                            const i = this.selectedAttrs.indexOf(code);

                            if (i >= 0) {
                                this.selectedAttrs.splice(i, 1);

                                this.forgetAttrDetail(code);

                                return;
                            }

                            this.selectedAttrs.push(code);

                            this.rememberAttrDetail(code);
                        },

                        rememberAttrDetail(code) {
                            const attribute = this.customAttributes.find(a => this.attributeCode(a) === code);

                            if (attribute) {
                                this.selectedAttrDetails[code] = attribute;
                            }
                        },

                        forgetAttrDetail(code) {
                            delete this.selectedAttrDetails[code];
                        },

                        clearSelectedAttrs() {
                            this.selectedAttrs = [];
                            this.selectedAttrDetails = {};
                            this.bulkGroup = null;
                            this.selectAllAcrossPages = false;
                            this.allMatchingAttributes = [];
                        },

                        onBulkGroup(value) {
                            try {
                                const option = JSON.parse(value);

                                this.bulkGroup = option && option.code ? option.code : null;
                            } catch (e) {
                                this.bulkGroup = null;
                            }
                        },

                        selectPage(on) {
                            this.selectAllAcrossPages = false;
                            this.allMatchingAttributes = [];

                            if (on) {
                                this.customAttributes.forEach(a => {
                                    const code = this.attributeCode(a);

                                    if (! this.selectedAttrs.includes(code)) {
                                        this.selectedAttrs.push(code);
                                    }

                                    this.rememberAttrDetail(code);
                                });
                            } else {
                                this.customAttributes.forEach(a => this.forgetAttrDetail(this.attributeCode(a)));

                                this.selectedAttrs = this.selectedAttrs.filter(
                                    code => ! this.customAttributes.find(a => this.attributeCode(a) === code)
                                );
                            }
                        },

                        selectAllMatching() {
                            if (this.isSelectingAll) {
                                return;
                            }

                            this.isSelectingAll = true;

                            const params = Object.assign({}, this.params, {
                                entityName: 'attributes',
                                page: 1,
                                perPage: this.serverTotalAttributes,
                                notInFamily: this.familyId,
                            });

                            this.$axios
                                .get(this.getAttributeRoute, { params })
                                .then(result => {
                                    this.allMatchingAttributes = result.data.options || [];
                                    this.selectedAttrs = this.allMatchingAttributes.map(a => this.attributeCode(a));
                                    this.selectAllAcrossPages = true;
                                })
                                .finally(() => {
                                    this.isSelectingAll = false;
                                });
                        },

                        assignBulk() {
                            if (! this.bulkGroup) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.select-group')" });

                                return;
                            }

                            const group = this.familyDefaultGroups.find(g => g.code === this.bulkGroup);

                            if (! group) {
                                return;
                            }

                            const moving = this.selectAllAcrossPages
                                ? this.allMatchingAttributes.slice()
                                : this.selectedAttrs
                                    .map(code => this.selectedAttrDetails[code]
                                        ?? this.customAttributes.find(a => this.attributeCode(a) === code))
                                    .filter(Boolean);

                            this.loadGroupAttributes(group).then(() => {
                                moving.forEach(attribute => group.customAttributes.push(attribute));

                                const movedCodes = moving.map(a => this.attributeCode(a));

                                this.markPendingAssignments(movedCodes);

                                this.customAttributes = this.customAttributes.filter(a => ! movedCodes.includes(this.attributeCode(a)));

                                this.clearSelectedAttrs();

                                this.signalUnsaved();

                                this.getAttributes();
                            });
                        },

                        assignGroup(params, { resetForm, setErrors }) {
                            const selectedGroups = JSON.parse(params.group);

                            (Array.isArray(selectedGroups) ? selectedGroups : [selectedGroups]).forEach(jsonObject => {
                                const groupId = this.groupFormId(jsonObject);

                                if (! groupId) {
                                    return;
                                }

                                const index = this.familyDefaultGroups.findIndex(obj => obj.code === jsonObject.code);

                                if (index == -1) {
                                    this.familyDefaultGroups.push({
                                        'id': groupId,
                                        'name': jsonObject.label,
                                        'code': jsonObject.code,
                                        'group_mapping_id' : '',
                                        'customAttributes': [],
                                        'attributesCount': 0,
                                        'attributesLoaded': true,
                                        'hide': false,
                                    });
                                }
                            });

                            resetForm();

                            this.$refs.assignGroupModal.close();

                            this.signalUnsaved();
                        },

                        groupSelected(group) {
                            this.selectedGroup = group;
                        },

                        isGroupContainsSku(group) {
                            return group.customAttributes.find(attribute => {
                                return this.isSkuAttribute(attribute);
                            });
                        },

                        removeGroup(group = null) {
                            this.$emitter.emit('open-confirm-modal', {
                                agree: () => {
                                    const groupToRemove = group || this.selectedGroup;

                                    if (! groupToRemove || ! groupToRemove.id) {
                                        this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.select-group')" });

                                        return;
                                    }

                                    this.loadGroupAttributes(groupToRemove).then(() => this.confirmGroupRemoval(groupToRemove));
                                }
                            });
                        },

                        confirmGroupRemoval(groupToRemove) {
                            if (this.isGroupContainsSku(groupToRemove)) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.group-contains-system-attributes')" });

                                return;
                            }

                            const index = this.familyDefaultGroups.findIndex(obj => obj.code === groupToRemove.code);

                            if (index === -1) {
                                return;
                            }

                            groupToRemove.customAttributes.forEach(attribute => {
                                if (! this.customAttributes.find(customAttribute => customAttribute.id === attribute.id)) {
                                    this.customAttributes.push(attribute);
                                }
                            });

                            this.familyDefaultGroups.splice(index, 1);

                            this.retainedGroupMappings = this.retainedGroupMappings.filter(
                                mappingId => Number(mappingId) !== Number(groupToRemove.group_mapping_id)
                            );

                            if (this.selectedGroup.code === groupToRemove.code) {
                                this.selectedGroup = {
                                    id: null,
                                    code: null,
                                    name: null,
                                };
                            }

                            this.signalUnsaved();
                        },

                        onChange(e, group) {
                            if (e.added?.element && group) {
                                e.added.element.group_id = this.groupFormId(group);

                                this.dropDuplicateInGroup(group, e.added.element);

                                this.markPendingAssignments([this.attributeCode(e.added.element)]);
                            }

                            this.$emitter.emit('assigned-attributes-changed', e);
                            this.signalUnsaved();
                        },

                        onUnassignedChange(e) {
                            if (e.added?.element) {
                                const changedAttribute = e.added.element;
                                const code = this.attributeCode(changedAttribute);

                                delete changedAttribute.group_id;

                                this.selectedAttrs = this.selectedAttrs.filter(selectedCode => selectedCode !== code);

                                this.forgetAttrDetail(code);

                                this.releasePendingAssignment(code);
                            }

                            this.$emitter.emit('assigned-attributes-changed', e);
                            this.signalUnsaved();

                            if (e.removed) {
                                this.getAttributes();
                            }
                        },

                        signalUnsaved() {
                            // Drag-assign mutates hidden inputs without a native event, so notify the unsaved-changes tracker.
                            this.dirtyTick++;

                            this.$nextTick(() => {
                                const marker = this.$el?.querySelector('[data-attribute-groups-dirty]');

                                if (! marker) {
                                    return;
                                }

                                marker.dispatchEvent(new Event('input', { bubbles: true }));
                                marker.dispatchEvent(new Event('change', { bubbles: true }));
                                marker.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                                    bubbles: true,
                                    detail: { name: '_attribute_groups_dirty' },
                                }));
                            });
                        },

                        changePage(page) {
                            page = Number(page);

                            if (page > 0 && page <= this.totalPages) {
                                this.currentPage = page;
                                this.getAttributes();
                            }
                        },

                        getAttributes() {
                            Object.assign(this.params, {
                                entityName: 'attributes',
                                page: this.currentPage,
                                notInFamily: this.familyId,
                            });

                            this.isLoading = true;

                            this.$axios
                                .get(this.getAttributeRoute, {params: this.params})
                                .then(result => {
                                    this.totalPages = result.data.lastPage || 1;
                                    this.serverTotalAttributes = result.data.total || 0;
                                    this.totalAttributes = Math.max(0, this.serverTotalAttributes - this.pendingAssignedCodes.length);

                                    if (! result.data.options.length && this.currentPage > this.totalPages) {
                                        this.currentPage = this.totalPages;
                                        this.getAttributes();

                                        return;
                                    }

                                    const assigned = this.assignedAttributeCodes();

                                    this.customAttributes = result.data.options.filter(
                                        option => ! assigned.has(this.attributeCode(option))
                                    );

                                    this.isLoading = false;
                                });
                        },

                        search(value) {
                            // Blur also fires `change`; skip the refetch that would tear the list down mid-click.
                            if (this.params.query === value) {
                                return;
                            }

                            this.params.query = value;
                            this.currentPage = 1;

                            this.getAttributes();
                        }
                    }
                });
            </script>
        @endPushOnce
    @endIf
</x-admin::layouts.with-history>
