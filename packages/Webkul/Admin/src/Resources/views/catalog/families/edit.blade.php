<x-admin::layouts.with-history>
    <x-slot:entityName>
        attributeFamily
    </x-slot>
    <x-slot:title>
        @lang('admin::app.catalog.families.edit.title')
    </x-slot>
    
    <!-- Input Form -->
    <x-admin::form
        method="PUT"
        :action="route('admin.catalog.families.update', $attributeFamily['family']->id)"
    >

        {!! view_render_event('unopim.admin.catalog.families.edit.edit_form_control.before', ['attributeFamily' => $attributeFamily]) !!}

        <!-- Page Header -->
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.catalog.families.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.catalog.families.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.catalog.families.edit.back-btn')
                </a>

                <button 
                    type="submit" 
                    class="primary-button"
                >
                    @lang('admin::app.catalog.families.edit.save-btn')
                </button>
            </div>
        </div>

        <!-- Container -->
        <div class="flex gap-2.5 mt-3.5">
            <!-- Left Container -->

            {!! view_render_event('unopim.admin.catalog.families.edit.card.attributes-panel.before', ['attributeFamily' => $attributeFamily]) !!}

            <div class="flex flex-col gap-2 flex-1 bg-white dark:bg-cherry-900 rounded box-shadow">
                <v-family-attributes>
                    <x-admin::shimmer.families.attributes-panel />
                </v-family-attributes>
            </div>

            {!! view_render_event('unopim.admin.catalog.families.edit.card.attributes-panel.after', ['attributeFamily' => $attributeFamily]) !!}

            {!! view_render_event('unopim.admin.catalog.families.edit.card.accordion.general.before', ['attributeFamily' => $attributeFamily]) !!}
        
            <!-- Right Container -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full select-none">
                <!-- General Pannel -->
                <div class="relative p-[16px] bg-white dark:bg-cherry-800 rounded-[4px] box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.catalog.attributes.edit.general')
                    </p>
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="!text-gray-800 dark:!text-white">
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
                        <!-- Locales Inputs -->
                        @foreach ($locales as $locale)
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    {{ $locale->name }}
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

    @pushOnce('scripts')
        <script 
            type="text/x-template" 
            id="v-family-attributes-template"
        >
            <div>
                <!-- Panel Header -->
                <div class="flex flex-wrap gap-2.5 justify-between mb-2.5 p-4">
                    <!-- Panel Header -->
                    <div class="flex flex-col gap-2">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.catalog.families.edit.attribute-groups')
                        </p>

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                            @lang('admin::app.catalog.families.edit.groups-info')
                        </p>
                    </div>
                    
                    <!-- Panel Content -->
                    <div class="flex gap-x-1 items-center">
                        <!-- Delete Group Button -->
                        <div
                            class="px-3 py-1.5 border-2 border-transparent rounded-md text-red-600 font-semibold whitespace-nowrap transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 cursor-pointer"
                            @click="deleteGroup"
                        >
                            @lang('admin::app.catalog.families.edit.delete-group-btn')
                        </div>

                        <!-- Add Group Button -->
                        <div
                            class="secondary-button"
                            @click="$refs.assignGroupModal.open()"
                        >
                            @lang('admin::app.catalog.families.edit.assign-group-btn')
                        </div>
                    </div>
                </div>
                <!-- Panel Content -->
                <div class="grid grid-cols-2 gap-4 mb-2.5 p-4">
                    <!-- Attributes Groups Container -->
                    <div class="">
                        <!-- Unassigned Attribute Groups Header -->
                        <div class="flex flex-col mb-4">
                            <p class="text-gray-600 dark:text-gray-300 font-semibold leading-6">
                                @lang('admin::app.catalog.families.edit.main-column')
                            </p>
                        </div>
                        <div class="flex flex-col mb-4" v-if="defaultFamilyGroups.length === 0">
                            <p class="ext-xs font-medium text-gray-500 dark:text-gray-300">
                                @lang('admin::app.catalog.families.edit.assign-first-attribute-group')
                            </p>
                        </div>

                        <!-- Draggable Unassigned Attribute Group  -->
                        <draggable
                            id="assigned-attribute-groups"
                            class="h-[calc(100vh-285px)] pb-[16px] overflow-auto ltr:border-r rtl:border-l border-gray-200"
                            ghost-class="draggable-ghost"
                            handle=".icon-drag"
                            v-bind="{animation: 200}"
                            :list="defaultFamilyGroups"
                            item-key="id"
                            group="groups"
                        >
                            <template #item="{ element, index }">
                                <div class="">
                                    <!-- Group Container -->
                                    <div class="flex items-center group">
                                        <!-- Toggle -->
                                        <i
                                            class="icon-chevron-down text-[20px] rounded-[6px] cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800 group-hover:text-gray-800"
                                            @click="element.hide = ! element.hide"
                                        >
                                        </i>
                                        <div
                                            class="group_node flex gap-[6px] max-w-max py-[6px] ltr:pr-[6px] rtl:pl-[6px] rounded transition-all text-gray-600 dark:text-gray-300 group cursor-pointer"
                                            :class="{'bg-violet-100 dark:text-violet-800 text-violet-600 group-hover:text-gray-800 dark:group-hover:text-violet-800': selectedGroup.id == element.id}"
                                            @click="groupSelected(element)"
                                        >
                                            <i class="icon-drag text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>

                                            <i
                                                class="text-xl text-inherit transition-all group-hover:text-gray-800 dark:group-hover:text-white"
                                                :class="[element.is_user_defined ? 'icon-folder' : 'icon-folder-block']"
                                            >
                                            </i>

                                            <span
                                                class="text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
                                                :class="{'bg-violet-100 dark:text-violet-800 text-violet-600 group-hover:text-gray-800 dark:group-hover:text-gray-800': selectedGroup.id == element.id}"
                                                v-text="element.name"
                                            >
                                            </span>
                            
                                            <input
                                                type="hidden"
                                                :name="'attribute_groups[' + element.id + '][position]'"
                                                :value="index + 1"
                                            />

                                            <input
                                                type="hidden"
                                                :name="'attribute_groups[' + element.id + '][attribute_groups_mapping]'"
                                                v-model="element.group_mapping_id"
                                            />
                                        </div>
                                    </div>
                                    <!-- Group Attributes -->
                                    <draggable
                                        class="ltr:ml-11 rtl:mr-11"
                                        ghost-class="draggable-ghost"
                                        handle=".icon-drag"
                                        v-bind="{animation: 200}"
                                        :list="getGroupAttributes(element)"
                                        item-key="id"
                                        group="attributes"
                                        @change="onChange"
                                        v-show="! element.hide"
                                    >
                                        <template #item="{ element, index }">
                                            <div class="flex gap-1.5 max-w-max py-1.5 ltr:pr-1.5 rtl:pl-1.5 rounded text-gray-600 dark:text-gray-300 group cursor-pointer">
                                                <i class="icon-drag text-[20px] transition-all group-hover:text-gray-700"></i>

                                                <span 
                                                    class="text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
                                                    v-text="element.name"    
                                                >
                                                </span>

                                                <input
                                                    type="hidden"
                                                    :name="'attribute_groups[' + element.group_id + '][custom_attributes][' + index + '][id]'"
                                                    v-model="element.id"
                                                />

                                                <input
                                                    type="hidden"
                                                    :name="'attribute_groups[' + element.group_id + '][custom_attributes][' + index + '][position]'"
                                                    :value="index + 1"
                                                />
                                            </div>  
                                        </template>
                                    </draggable>
                                </div>  
                            </template>
                        </draggable>
                    </div>

                    <!-- Unassigned Attributes Container -->
                    <div>
                        <!-- Unassigned Attributes Header -->
                        <div class="flex justify-between mb-4">
                            <div>
                                <p class="text-gray-600 dark:text-gray-300 font-semibold leading-6">
                                    @lang('admin::app.catalog.families.edit.unassigned-attributes')
                                </p>
    
                                <p class="text-xs text-gray-800 dark:text-white font-medium ">
                                    @lang('admin::app.catalog.families.edit.unassigned-attributes-info')
                                </p>
                            </div>

                            <span class="icon-search text-2xl ltr:right-5 rtl:left-3 top-1.5 flex items-center cursor-pointer" v-if="!isSearching" @click="isSearching=true"></span>

                        </div>

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
                            <div class="relative w-full flex items-center justify-center mb-3" :class="isSearching ? '' : 'hidden'">
                                <input
                                    type="text"
                                    class="bg-white dark:bg-cherry-800 border dark:border-cherry-900 rounded-lg block w-full ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 py-1.5 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400"
                                    placeholder="Search"
                                    v-model.lazy="searchTerm"
                                    v-debounce="500"
                                    @keydown.enter.prevent="search($event.target.value)"
                                />

                                <span class="icon-search text-2xl absolute ltr:right-5 rtl:left-3 top-1.5 flex items-center pointer-events-none"></span>
                            </div>
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
                            >
                                <template #item="{ element, index }">
                                    <div class="flex gap-1.5 max-w-max py-1.5 ltr:pr-1.5 rtl:pl-1.5 rounded text-gray-600 dark:text-gray-300 group">
                                            <i class="icon-drag text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>
                                            <i class="text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white"></i>
                                            <span 
                                                class="text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
                                                v-text="element.name"
                                            >
                                            </span>
                                    </div>   
                                </template>
                            </draggable>

                            <!-- Pagination -->
                            <div class="flex gap-1 items-left justify-right mt-2.5">
                                <a @click="changePage(currentPage - 1)">
                                    <div class="inline-flex gap-x-1 items-center justify-between w-full max-w-max ltr:ml-2 rtl:mr-2 p-1.5 bg-white dark:bg-cherry-800 border rounded-md dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center cursor-pointer transition-all hover:border hover:bg-violet-50 dark:hover:bg-cherry-800 marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black">
                                        <span class="icon-chevron-left text-2xl"></span>
                                    </div>
                                </a>

                                <a @click="changePage(currentPage + 1)">
                                    <div
                                        class="inline-flex gap-x-1 items-center justify-between w-full max-w-max ltr:ml-2 rtl:mr-2 p-1.5 bg-white dark:bg-cherry-800 border rounded-md dark:border-cherry-800 text-gray-600 dark:text-gray-300 text-center cursor-pointer transition-all hover:border hover:bg-violet-50 dark:hover:bg-cherry-800 marker:shadow appearance-none focus:ring-2 focus:outline-none focus:ring-black">
                                        <span class="icon-chevron-right text-2xl"></span>
                                    </div>
                                </a>

                                <div class="flex items-center justify-center gap-2.5">
                                    <input
                                        type="text"
                                        class="inline-flex min-h-[38px] max-w-[60px] appearance-none items-center justify-center gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-3 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                                        :value="currentPage"
                                        @keydown.enter.prevent="changePage($event.target.value)"
                                    >
        
                                    <div class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                                        <span> @lang('admin::app.components.datagrid.toolbar.of') </span>
        
                                        <span v-text="totalPages"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, assignGroup)">
                        <!-- Model Form -->
                        <x-admin::modal ref="assignGroupModal">
                            <!-- Model Header -->
                            <x-slot:header>
                                <p class="text-lg text-gray-800 dark:text-white font-bold">
                                    @lang('admin::app.catalog.families.edit.assign-group-title')
                                </p>
                            </x-slot>

                            <!--Model Content -->
                            <x-slot:content>
                                <!-- Group List -->
                                <x-admin::form.control-group class="mb-4">
                                    <x-admin::form.control-group.label class="required font-medium">
                                        @lang('admin::app.catalog.families.edit.groups')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="group"
                                        rules="required"
                                        :label="trans('admin::app.catalog.families.edit.groups')"
                                        async="true"
                                        entity-name="attribute_group"
                                        track-by="id"
                                        label-by="label"
                                    />

                                    <x-admin::form.control-group.error control-name="group" /> 
                                </x-admin::form.control-group>
                            </x-slot>

                            <!-- Model Footer -->
                            <x-slot:footer>
                                <div class="flex gap-x-2.5 items-center">
                                    <!-- Add Group Button -->
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
                        isSearching: false,
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
                        params: {},
                    }
                },

                computed: {
                    defaultFamilyGroups() {
                        return this.familyDefaultGroups;
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
                    },

                    getGroupAttributes(group) {
                        group.customAttributes.forEach((attribute, index) => {
                            attribute.group_id = group.id;
                        });

                        return group.customAttributes;
                    },

                    assignGroup(params, { resetForm, setErrors }) {
                        const jsonObject = JSON.parse(params.group);
                        const index = this.familyDefaultGroups.findIndex(obj => obj.code === jsonObject.code);

                        if (index == -1) {
                            this.familyDefaultGroups.push({
                                'id': jsonObject.id,
                                'name': jsonObject.label,
                                'code': jsonObject.code,
                                'group_mapping_id' : '',
                                'customAttributes': [],
                            });
                        }

                        resetForm();

                        this.$refs.assignGroupModal.close();
                    },

                    groupSelected(group) {
                        this.selectedGroup = group;
                    },

                    isGroupContainsSystemAttributes(group) {
                        return group.customAttributes.find(attribute => ! attribute.is_user_defined);
                    },

                    deleteGroup() {
                        this.$emitter.emit('open-confirm-modal', {
                            agree: () => {
                                if (! this.selectedGroup.id) {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.select-group')" });

                                    return;
                                }

                                if (this.isGroupContainsSystemAttributes(this.selectedGroup)) {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.families.edit.group-contains-system-attributes')" });

                                    return;
                                }
                        
                                const index = this.familyDefaultGroups.findIndex(obj => obj.code === this.selectedGroup.code);

                                if (index !== -1) {
                                    this.familyDefaultGroups.splice(index, 1);
                                }
                            }
                        });
                    },

                    onChange(e) {
                        this.$emitter.emit('assigned-attributes-changed', e);
                    },

                    changePage(page) {
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
                                this.customAttributes = result.data.options;

                                this.totalPages = result.data.lastPage;

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
</x-admin::layouts.with-history>
