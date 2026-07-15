@php
    $activeTab = match (true) {
        request()->has('variants')     => 'variants',
        request()->has('completeness') => 'completeness',
        request()->has('history')      => 'history',
        default                        => 'general',
    };

    $tabItems = [
        [
            'key'   => 'general',
            'url'   => '?',
            'label' => 'admin::app.components.layouts.sidebar.general',
        ],
        [
            'key'   => 'variants',
            'url'   => '?variants',
            'label' => 'admin::app.catalog.families.edit.variants',
        ],
        [
            'key'   => 'completeness',
            'url'   => '?completeness',
            'label' => 'completeness::app.components.layouts.sidebar.completeness',
        ],
    ];
@endphp

<x-admin::layouts.with-history
    :activeTab="$activeTab"
    :tab-items="$tabItems"
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
                                <x-admin::form.control-group>
                                    @foreach ($locales as $locale)
                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label
                                                class="w-full"
                                                localizable="true"
                                                :current-locale-code="$locale->code"
                                            >
                                                @lang('admin::app.catalog.families.edit.name')
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="text"
                                                :name="$locale->code . '[name]'"
                                                :value="old($locale->code)['name'] ?? ($attributeFamily['family']->translate($locale->code)->name ?? '')"
                                            />

                                            <x-admin::form.control-group.error :control-name="$locale->code . '[name]'" />
                                        </x-admin::form.control-group>
                                    @endforeach
                                </x-admin::form.control-group>
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

                    <!-- Panel Header -->
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
                            <!-- Add Group Button -->
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
                            <!-- Unassigned Attribute Groups Header -->
                            <x-admin::list.panel-header
                                :title="trans('admin::app.catalog.families.edit.main-column')"
                                :description="trans('admin::app.catalog.families.edit.main-column-info')"
                                searching="isSearchingAssigned"
                            >
                                <x-admin::search.field
                                    icon-position="left"
                                    :placeholder="trans('admin::app.catalog.families.edit.search')"
                                    v-model.trim="assignedSearchTerm"
                                    clear-when="assignedSearchTerm"
                                    clear-action="assignedSearchTerm = ''"
                                />
                            </x-admin::list.panel-header>

                            <!-- Draggable Unassigned Attribute Group  -->
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
                                        <!-- Group Container -->
                                            <x-admin::catalog.families.group-row
                                                :remove-title="trans('admin::app.catalog.families.edit.remove-group-btn')"
                                            />

                                        <!-- Group Attributes -->
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
                                                @change="onChange"
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
                        </div>

                        <div>
                            <!-- Unassigned Attributes Header -->
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

                                <x-admin::catalog.families.bulk-assign
                                    :select-group-placeholder="trans('admin::app.catalog.families.edit.select-destination-group')"
                                />

                                <!-- Draggable Unassigned Attributes -->
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

                                <!-- Pagination -->
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
                            isSearching: false,
                            isSearchingAssigned: false,
                            selectedGroup: {
                                id: null,
                                code: null,
                                name: null,
                            },
                            getAttributeRoute: "{{ route('admin.catalog.options.fetch-all')}}",
                            customAttributes: [],
                            familyDefaultGroups: @json($attributeFamily['familyGroupMappings']),
                            dropReverted: false,
                            searchTerm: '',
                            assignedSearchTerm: '',
                            params: {},
                            selectedAttrs: [],
                            bulkGroup: null,
                            dirtyTick: 0,
                        }
                    },

                    computed: {
                        defaultFamilyGroups() {
                            return this.familyDefaultGroups;
                        },

                        visibleFamilyGroups() {
                            if (! this.assignedSearchTerm) {
                                return this.familyDefaultGroups;
                            }

                            const term = this.assignedSearchTerm.toLowerCase();

                            return this.familyDefaultGroups.filter(group => {
                                return this.matchesSearch(group, term)
                                    || group.customAttributes.some(attribute => this.matchesSearch(attribute, term));
                            });
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

                        formattedTotalAttributes() {
                            return new Intl.NumberFormat().format(this.totalAttributes);
                        },

                        assignedGroupExcludeParams() {
                            return {
                                exclude: {
                                    columnName: 'code',
                                    values: this.familyDefaultGroups.map(group => group.code),
                                },
                            };
                        },

                        assignedAttributes() {
                            return this.familyDefaultGroups.map(group => {
                                return group.customAttributes.map(attribute => {
                                    return attribute.code
                                })
                            }).flat();
                        }
                    },

                    mounted() {
                        this.getAttributes();
                    },

                    methods: {
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
                            const i = this.selectedAttrs.indexOf(code);

                            i >= 0 ? this.selectedAttrs.splice(i, 1) : this.selectedAttrs.push(code);
                        },

                        clearSelectedAttrs() {
                            this.selectedAttrs = [];
                            this.bulkGroup = null;
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
                            if (on) {
                                this.customAttributes.forEach(a => {
                                    const code = this.attributeCode(a);

                                    if (! this.selectedAttrs.includes(code)) {
                                        this.selectedAttrs.push(code);
                                    }
                                });
                            } else {
                                this.selectedAttrs = this.selectedAttrs.filter(
                                    code => ! this.customAttributes.find(a => this.attributeCode(a) === code)
                                );
                            }
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

                            const moving = this.customAttributes.filter(a => this.selectedAttrs.includes(this.attributeCode(a)));

                            moving.forEach(attribute => group.customAttributes.push(attribute));

                            const movedCodes = moving.map(a => this.attributeCode(a));

                            this.customAttributes = this.customAttributes.filter(a => ! movedCodes.includes(this.attributeCode(a)));

                            this.clearSelectedAttrs();

                            this.signalUnsaved();

                            this.getAttributes();
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

                                    if (this.isGroupContainsSku(groupToRemove)) {
                                        this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.group-contains-system-attributes')" });

                                        return;
                                    }

                                    const index = this.familyDefaultGroups.findIndex(obj => obj.code === groupToRemove.code);

                                    if (index !== -1) {
                                        groupToRemove.customAttributes.forEach(attribute => {
                                            if (! this.customAttributes.find(customAttribute => customAttribute.id === attribute.id)) {
                                                this.customAttributes.push(attribute);
                                            }
                                        });

                                        this.familyDefaultGroups.splice(index, 1);

                                        if (this.selectedGroup.code === groupToRemove.code) {
                                            this.selectedGroup = {
                                                id: null,
                                                code: null,
                                                name: null,
                                            };
                                        }

                                        this.signalUnsaved();
                                    }
                                }
                            });
                        },

                        onChange(e) {
                            this.$emitter.emit('assigned-attributes-changed', e);
                            this.signalUnsaved();
                        },

                        onUnassignedChange(e) {
                            const changedAttribute = e.added?.element || e.removed?.element;

                            if (changedAttribute) {
                                const code = this.attributeCode(changedAttribute);

                                delete changedAttribute.group_id;

                                this.selectedAttrs = this.selectedAttrs.filter(selectedCode => selectedCode !== code);
                            }

                            this.$emitter.emit('assigned-attributes-changed', e);
                            this.signalUnsaved();

                            if (e.removed) {
                                this.getAttributes();
                            }
                        },

                        signalUnsaved() {
                            // Drag-assigning attributes/groups mutates hidden inputs without a
                            // native input/change event, so tell the unsaved-changes tracker.
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
                                exclude: {
                                    columnName: 'code',
                                    values: this.assignedAttributes
                                }
                            });

                            this.isLoading = true;

                            this.$axios
                                .get(this.getAttributeRoute, {params: this.params})
                                .then(result => {
                                    this.totalPages = result.data.lastPage || 1;
                                    this.totalAttributes = result.data.total || 0;

                                    if (! result.data.options.length && this.currentPage > this.totalPages) {
                                        this.currentPage = this.totalPages;
                                        this.getAttributes();

                                        return;
                                    }

                                    this.customAttributes = result.data.options;

                                    this.isLoading = false;
                                });
                        },

                        search(value) {
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
