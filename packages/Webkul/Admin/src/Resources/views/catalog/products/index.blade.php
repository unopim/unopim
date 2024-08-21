<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.catalog.products.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Export Modal -->
            <x-admin::datagrid.export src="{{ route('admin.catalog.products.index') }}" />

            {!! view_render_event('unopim.admin.catalog.products.create.before') !!}

            @if (bouncer()->hasPermission('catalog.products.create'))
                <v-create-product-form>
                    <button
                        type="button"
                        class="primary-button"
                    >
                        @lang('admin::app.catalog.products.index.create-btn')
                    </button>
                </v-create-product-form>
            @endif

            {!! view_render_event('unopim.admin.catalog.products.create.after') !!}
        </div>
    </div>

    {!! view_render_event('unopim.admin.catalog.products.list.before') !!}

    <!-- Datagrid -->
    <x-admin::datagrid src="{{ route('admin.catalog.products.index') }}" :isMultiRow="true">
        <!-- Datagrid Header -->
        @php
            $hasPermission = bouncer()->hasPermission('catalog.products.edit') || bouncer()->hasPermission('catalog.products.delete');
        @endphp

        <template #header="{ columns, records, sortPage, selectAllRecords, applied, isLoading}">
            <template v-if="! isLoading">
                <div class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 items-center px-4 py-2.5 border-b bg-violet-50 dark:bg-cherry-900 dark:border-cherry-800 ">
                    <div
                        class="flex gap-2.5 items-center select-none"
                        v-for="(columnGroup, index) in [['product_id', 'sku', 'attribute_family'], ['status', 'type']]"
                    >
                        @if ($hasPermission)
                            <label
                                class="flex gap-1 items-center w-max cursor-pointer select-none"
                                for="mass_action_select_all_records"
                                v-if="! index"
                            >
                                <input
                                    type="checkbox"
                                    name="mass_action_select_all_records"
                                    id="mass_action_select_all_records"
                                    class="hidden peer"
                                    :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                    @change="selectAllRecords"
                                >

                                <span
                                    class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                                    :class="[
                                        applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checkbox-check peer-checked:text-violet-700' : (
                                            applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-violet-700' : ''
                                        ),
                                    ]"
                                >
                                </span>
                            </label>
                        @endif

                        <p class="text-gray-600 dark:text-gray-300">
                            <span class="[&>*]:after:content-['_/_']">
                                <template v-for="column in columnGroup">
                                    <span
                                        class="after:content-['/'] last:after:content-['']"
                                        :class="{
                                            'text-gray-800 dark:text-white font-medium': applied.sort.column == column,
                                            'cursor-pointer hover:text-gray-800 dark:hover:text-white': columns.find(columnTemp => columnTemp.index === column)?.sortable,
                                        }"
                                        @click="
                                            columns.find(columnTemp => columnTemp.index === column)?.sortable ? sortPage(columns.find(columnTemp => columnTemp.index === column)): {}
                                        "
                                    >
                                        @{{ columns.find(columnTemp => columnTemp.index === column)?.label }}
                                    </span>
                                </template>
                            </span>

                            <i
                                class="ltr:ml-1.5 rtl:mr-1.5 text-base text-gray-800 dark:text-white align-text-bottom"
                                :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                v-if="columnGroup.includes(applied.sort.column)"
                            ></i>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2.5 items-center justify-end select-none">
                        <p
                            class="text-gray-600 dark:text-gray-300"
                        >
                            @lang('admin::app.components.datagrid.table.actions')
                        </p>
                    </div>
                </div>
            </template>

            <!-- Datagrid Head Shimmer -->
            <template v-else>
                <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
            </template>
        </template>

        <!-- Datagrid Body -->
        <template #body="{ columns, records, performAction, setCurrentSelectionMode, applied, isLoading }">
            <template v-if="! isLoading">
                <div
                    class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 px-4 py-2.5 border-b dark:border-cherry-800  transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                    v-for="record in records"
                >
                    <!-- Name, SKU, Attribute Family Columns -->
                    <div class="flex gap-2.5">
                        @if ($hasPermission)
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.product_id}`"
                                :id="`mass_action_select_record_${record.product_id}`"
                                :value="record.product_id"
                                class="hidden peer"
                                v-model="applied.massActions.indices"
                                @change="setCurrentSelectionMode"
                            >

                            <label
                                class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"
                                :for="`mass_action_select_record_${record.product_id}`"
                            ></label>
                        @endif

                        <div class="flex flex-col gap-1.5">
                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ "@lang('admin::app.catalog.products.index.datagrid.id-value')".replace(':id', record.product_id) }}
                            </p>
                            <p
                                class="text-gray-600 dark:text-gray-300"
                            >
                                @{{ "@lang('admin::app.catalog.products.index.datagrid.sku-value')".replace(':sku', record.sku) }}
                            </p>

                            <p
                                class="text-gray-600 dark:text-gray-300"
                            >
                                @{{ "@lang('admin::app.catalog.products.index.datagrid.attribute-family-value')".replace(':attribute_family', record.attribute_family) }}
                            </p>
                        </div>
                    </div>

                    <!-- Id and Parent -->
                    <div class="flex gap-1.5 overflow-hidden">
                        <div class="flex flex-col gap-1.5">
                            <p :class="['true' == record?.status?.toLowerCase() ? 'label-active': 'label-info']">
                                @{{ 'true' == record?.status?.toLowerCase() ? "@lang('admin::app.common.enable')" : "@lang('admin::app.common.disable')" }}
                            </p>

                            <p
                                class="text-gray-600 dark:text-gray-300"
                                v-text="record.type"
                            >
                            </p>
                        </div>
                    </div>

                    <!-- Status, Category, Type Columns -->
                    <div class="flex gap-x-4 justify-between items-center">
                        <div class="flex flex-col gap-1.5">
                            
                        </div>

                        <div class="flex gap-1.5 items-center">
                            <span
                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                :class="action.icon"
                                v-text="!action.icon ? action.title : ''"
                                v-for="action in record.actions"
                                :title="action.title ?? ''"
                                @click="performAction(action)"
                            >
                            </span>
                        </div>
                    </div>

                    
                </div>
            </template>

            <!-- Datagrid Body Shimmer -->
            <template v-else>
                <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
            </template>
        </template>
    </x-admin::datagrid>

    {!! view_render_event('unopim.admin.catalog.products.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-create-product-form-template">
            <div>
                <!-- Product Create Button -->
                @if (bouncer()->hasPermission('catalog.products.create'))
                    <button
                        type="button"
                        class="primary-button"
                        @click="$refs.productCreateModal.toggle()"
                    >
                        @lang('admin::app.catalog.products.index.create-btn')
                    </button>
                @endif

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, create)" ref="productCreateForm">
                        <!-- Customer Create Modal -->
                        <x-admin::modal ref="productCreateModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <p
                                    class="text-lg text-gray-800 dark:text-white font-bold"
                                    v-if="! attributes.length"
                                >
                                    @lang('admin::app.catalog.products.index.create.title')
                                </p>

                                <p
                                    class="text-lg text-gray-800 dark:text-white font-bold"
                                    v-else
                                >
                                    @lang('admin::app.catalog.products.index.create.configurable-attributes')
                                </p>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <div v-show="! attributes.length">
                                    {!! view_render_event('unopim.admin.catalog.products.create_form.general.controls.before') !!}

                                    <!-- Product Type -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.type')
                                        </x-admin::form.control-group.label>

                                        @php
                                            $supportedTypes  = config('product_types');

                                            $types = [];

                                            foreach($supportedTypes as $id => $type) {
                                                $types[] = [
                                                    'id'    => $id,
                                                    'label' => trans($type['name'])
                                                ];
                                            }
                                        @endphp

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="type"
                                            rules="required"
                                            :label="trans('admin::app.catalog.products.index.create.type')"
                                            :options="json_encode($types)"
                                            track-by="id"
                                            label-by="label"
                                        >
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="type" />
                                    </x-admin::form.control-group>
                                    
                                    @php
                                        $types = [];
                                        foreach($families as $family) {
                                            $types[] = [
                                                'id'    => $family->id,
                                                'label' => ! empty($family->name) ? $family->name : '[' . $family->code . ']',
                                            ];
                                        }
                                        
                                        $typesJson = json_encode($types);

                                    @endphp
                                    
                                    <!-- Attribute Family Id -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.family')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="attribute_family_id"
                                            rules="required"
                                            :label="trans('admin::app.catalog.products.index.create.family')"
                                            :options="$typesJson"
                                            track-by="id"
                                            label-by="label"
                                        >
                                            
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="attribute_family_id" />
                                    </x-admin::form.control-group>

                                    <!-- SKU -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.sku')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="sku"
                                            ::rules="{ required: true, regex: /^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/ }"
                                            :label="trans('admin::app.catalog.products.index.create.sku')"
                                        />

                                        <x-admin::form.control-group.error control-name="sku" />
                                    </x-admin::form.control-group>

                                    {!! view_render_event('unopim.admin.catalog.products.create_form.general.controls.before') !!}
                                </div>

                                <div v-show="attributes.length">
                                    {!! view_render_event('unopim.admin.catalog.products.create_form.attributes.controls.before') !!}

                                    <div
                                        class="mb-2.5"
                                    >
                                        <label
                                            class="block leading-6 text-xs text-gray-800 dark:text-white font-medium"
                                        >
                                        </label>

                                        <div class="flex flex-wrap gap-1 min-h-[38px] p-1.5 border dark:border-cherry-800 rounded-md">
                                            <p
                                                class="flex items-center py-1 px-2 bg-violet-100 rounded text-violet-700 font-semibold"
                                                v-for="attribute in attributes"
                                            >
                                                @{{ attribute.name }}

                                                <span
                                                    class="icon-cancel cursor-pointer text-lg text-violet-700 ltr:ml-1.5 rtl:mr-1.5 dark:!text-violet-700"
                                                    @click="removeAttribute(attribute)"
                                                >
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    {!! view_render_event('unopim.admin.catalog.products.create_form.attributes.controls.before') !!}
                                </div>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <!-- Modal Submission -->
                                <div class="flex gap-x-2.5 items-center">
                                    <button
                                        type="button"
                                        class="transparent-button hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white"
                                        v-if="attributes.length"
                                        @click="attributes = []"
                                    >
                                        @lang('admin::app.catalog.products.index.create.back-btn')
                                    </button>

                                    <button
                                        type="submit"
                                        class="primary-button"
                                    >
                                        @lang('admin::app.catalog.products.index.create.save-btn')
                                    </button>
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-product-form', {
                template: '#v-create-product-form-template',

                data() {
                    return {
                        attributes: [],

                        superAttributes: {}
                    };
                },

                methods: {
                    create(params, { setErrors }) {
                        let formData = new FormData(this.$refs.productCreateForm);

                        this.attributes.forEach(attribute => {
                            params.super_attributes ||= {};

                            params.super_attributes[attribute.code] = this.superAttributes[attribute.code];
                        });

                        if (this.attributes?.length > 0) {
                            formData.append('super_attributes', JSON.stringify(params.super_attributes));
                        }

                        this.$axios.post("{{ route('admin.catalog.products.store') }}", formData)
                            .then((response) => {
                                if (response.data.data.redirect_url) {
                                    window.location.href = response.data.data.redirect_url;
                                } else {
                                    this.attributes = response.data.data.attributes;

                                    this.setSuperAttributes();
                                }
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    removeAttribute(attribute) {
                        this.attributes = this.attributes.filter(item => item.id != attribute.id);

                        this.setSuperAttributes();
                    },

                    setSuperAttributes() {
                        this.superAttributes = {};

                        this.attributes.forEach(attribute => {
                            this.superAttributes[attribute.code] = attribute.code;
                        });
                    }
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
