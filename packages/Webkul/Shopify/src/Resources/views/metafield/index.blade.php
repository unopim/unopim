<x-admin::layouts>
    <x-slot:title>
        @lang('shopify::app.shopify.metafield.index.title')
    </x-slot>

    <v-metafield>
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('shopify::app.shopify.metafield.index.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Create User Button -->
                @if (bouncer()->hasPermission('shopify.meta-fields.create'))
                    <button
                        type="button"
                        class="primary-button"
                    >
                        @lang('shopify::app.shopify.metafield.index.create')
                    </button>
                @endif
            </div>
        </div>

        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />
    </v-metafield>
    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-metafield-template"
        >
            <div class="flex justify-between items-center">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('shopify::app.shopify.metafield.index.title')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    <!-- User Create Button -->
                    @if (bouncer()->hasPermission('shopify.meta-fields.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="$refs.metafieldCreateModal.open()"
                        >
                            @lang('shopify::app.shopify.metafield.index.create')
                        </button>
                    @endif
                </div>
            </div>
            <!-- Datagrid -->
            <x-admin::datagrid :src="route('shopify.metafield.index')" ref="datagrid" class="mb-8"/>
            <!-- Modal Form -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, create)"
                    ref="metafieldCreateForm"
                >
                    <!-- User Create Modal -->
                    <x-admin::modal ref="metafieldCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                             <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('shopify::app.shopify.metafield.index.create')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <!-- Defintion Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.metafield.index.definitiontype')
                                </x-admin::form.control-group.label>
                                    @php
                                        $metaType = [
                                            [
                                                'id' => 'PRODUCT',
                                                'name' => 'Products',
                                            ], [
                                                'id' => 'PRODUCTVARIANT',
                                                'name' => 'Variants',
                                            ],
                                        ];
                                        $metaType = json_encode($metaType, true);
                                        $attributeType = ['text', 'textarea', 'boolean', 'select', 'multiselect', 'date', 'image'];
                                    @endphp
                                <x-admin::form.control-group.control
                                    type="select"
                                    id="ownerType"
                                    name="ownerType"
                                    rules="required"
                                    :options="$metaType"
                                    :label="trans('shopify::app.shopify.metafield.index.title')"
                                    :placeholder="trans('shopify::app.shopify.metafield.index.title')"
                                    @input="handleownerTypeChnage($event, 'code')"
                                    track-by="id"
                                    label-by="name"
                                />

                                <x-admin::form.control-group.error control-name="ownerType"/>
                            </x-admin::form.control-group>

                            <!-- Unopim Attribute -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.metafield.index.attribute')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="code"
                                    name="code"
                                    rules="required"
                                    track-by="code"
                                    label-by="label"
                                    :label="trans('shopify::app.shopify.metafield.index.attribute')"
                                    async=true
                                    :entityName="json_encode($attributeType)"
                                    :placeholder="trans('shopify::app.shopify.metafield.index.attribute')"
                                    :list-route="route('admin.shopify.get-attribute')"
                                    @input="handleSelectChange($event, 'code')"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>


                            <!-- Content Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.metafield.index.ContentTypeName')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="type"
                                    name="type"
                                    rules="required"
                                    track-by="id"
                                    label-by="name"
                                    v-model="selectedType"
                                    ::options="contentTypeOptions"
                                    @input="handleContentTypeChnage($event, 'code')"
                                    :label="trans('shopify::app.shopify.metafield.index.ContentTypeName')"
                                    :placeholder="trans('shopify::app.shopify.metafield.index.ContentTypeName')"
                                    ref="mediaAttributes"
                                />

                                <x-admin::form.control-group.error control-name="type"/>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group v-if=" (urlvalidation == true)">
                                <x-admin::form.control-group.label>
                                    @lang('shopify::app.shopify.metafield.index.urlvalidation')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.label>
                                     <span class="icon-information text-lg"></span> <p class="break-words text-xs text-gray-500 dark:text-gray-400"> @lang('shopify::app.shopify.metafield.index.urlvalidationdata')</p>
                                </x-admin::form.control-group.label>
                            </x-admin::form.control-group>

                            <div class="flex items-center gap-4" v-if=" (contenttypeSelect == 1)">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.control
                                        type="radio"
                                        id="is_unique_onevalue"
                                        name="listvalue"
                                        value="0"
                                        for="is_unique_onevalue"
                                        @change="toggleOneValue($event)"
                                        checked
                                    />

                                    <x-admin::form.control-group.label
                                        for="is_unique_onevalue"
                                    >
                                        One value
                                    </x-admin::form.control-group.label>
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.control
                                        type="radio"
                                        id="is_unique_listvalue"
                                        name="listvalue"
                                        value="1"
                                        for="is_unique_listvalue"
                                        @change="toggleOneValue($event)"
                                    />

                                    <x-admin::form.control-group.label
                                        for="is_unique_listvalue"
                                    >
                                        List of values
                                    </x-admin::form.control-group.label>
                                </x-admin::form.control-group>
                            </div>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.metafield.index.attributes')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="attribute"
                                    name="attribute"
                                    rules="required"
                                    v-model="attribute"
                                    :label="trans('shopify::app.shopify.metafield.index.attributes')"
                                    :placeholder="trans('shopify::app.shopify.metafield.index.attributes')"
                                />

                                <x-admin::form.control-group.error control-name="attribute"/>
                            </x-admin::form.control-group>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('shopify::app.shopify.metafield.index.name_space_key')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="name_space_key"
                                    name="name_space_key"
                                    rules="required"
                                    v-model="name_space_key"
                                    :label="trans('shopify::app.shopify.metafield.index.name_space_key')"
                                    :placeholder="trans('shopify::app.shopify.metafield.index.name_space_key')"
                                />

                                <x-admin::form.control-group.error control-name="name_space_key"/>
                            </x-admin::form.control-group>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('shopify::app.shopify.metafield.index.description')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="description"
                                    name="description"
                                    :label="trans('shopify::app.shopify.metafield.index.description')"
                                    :placeholder="trans('shopify::app.shopify.metafield.index.description')"
                                />

                                <x-admin::form.control-group.error control-name="description"/>
                            </x-admin::form.control-group>
                            <div v-if=" (typeofminmx == 'text')">
                                <div :class="{ 'flex items-center gap-2':  width != null }">
                                    <x-admin::form.control-group ::class="width">
                                        <x-admin::form.control-group.label v-text="minvalueLabel">
                                        </x-admin::form.control-group.label>
                                        <x-admin::form.control-group.control
                                            type="text"
                                            id="minvalue"
                                            name="minvalue"
                                            ::label="minvalueLabel"
                                            ::placeholder="minvalueLabel"
                                            value=""
                                        />
                                        <x-admin::form.control-group.error control-name="minvalue"/>
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if=" (width != null)" class="w-[170px] mt-5">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            id="minunit"
                                            name="minunit"
                                            track-by="id"
                                            label-by="name"
                                            ::options="unitTypeOptions"
                                            label="min unit"
                                            placeholder="min unit"
                                            value=""
                                            ref="minunitvalue"
                                        />
                                        <x-admin::form.control-group.error control-name="minunit"/>
                                    </x-admin::form.control-group>
                                </div>
                                <div :class="{ 'flex items-center gap-2':  width != null }">
                                    <x-admin::form.control-group ::class="width">
                                        <x-admin::form.control-group.label v-text="maxvalueLabel">
                                        </x-admin::form.control-group.label>
                                        <x-admin::form.control-group.control
                                            type="text"
                                            id="maxvalue"
                                            name="maxvalue"
                                            ::label="maxvalueLabel"
                                            ::placeholder="maxvalueLabel"
                                            value=""
                                        />

                                        <x-admin::form.control-group.error control-name="maxvalue"/>
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if=" (width != null)" class="w-[170px] mt-5">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            id="maxunit"
                                            name="maxunit"
                                            track-by="id"
                                            label-by="name"
                                            ::options="unitTypeOptions"
                                            label="max unit"
                                            placeholder="max unit"
                                            value=""
                                            ref="maxunitvalue"
                                        />
                                        <x-admin::form.control-group.error control-name="maxunit"/>
                                    </x-admin::form.control-group>
                                </div>
                            </div>
                            <div v-if=" (typeofminmx == 'date')">
                                <x-admin::form.control-group>
                                <x-admin::form.control-group.label v-text="minvalueLabel">
                                </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="date"
                                        id="minvalue"
                                        name="minvalue"
                                        ::label="minvalueLabel"
                                        ::placeholder="minvalueLabel"
                                        value=""
                                    />

                                    <x-admin::form.control-group.error control-name="minvalue"/>
                                </x-admin::form.control-group>
                                <x-admin::form.control-group>
                                <x-admin::form.control-group.label v-text="maxvalueLabel">
                                </x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control
                                        type="date"
                                        id="maxvalue"
                                        name="maxvalue"
                                        ::label="maxvalueLabel"
                                        ::placeholder="maxvalueLabel"
                                        ref="maxvalueref"
                                        value=""
                                    />

                                    <x-admin::form.control-group.error control-name="maxvalue"/>
                                </x-admin::form.control-group>
                            </div>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('shopify::app.shopify.metafield.datagrid.pin')
                                </x-admin::form.control-group.label>
                                <input 
                                    type="hidden"
                                    name="pin"
                                    value="0"
                                />
                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="pin"
                                    value="1"
                                    :checked="true"
                                />
                                <x-admin::form.control-group.error control-name="pin"/>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group v-if=" (adminFilterable == 1)">
                                <x-admin::form.control-group.label>
                                    @lang('shopify::app.shopify.metafield.index.adminFilterable')
                                </x-admin::form.control-group.label>
                                <input 
                                    type="hidden"
                                    name="adminFilterable"
                                    value="0"
                                />
                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="adminFilterable"
                                    value="1"
                                />
                            </x-admin::form.control-group>
                            <x-admin::form.control-group v-if=" (smartCollectionCondition == 1)">
                                <x-admin::form.control-group.label>
                                    @lang('shopify::app.shopify.metafield.index.smartCollectionCondition')
                                </x-admin::form.control-group.label>
                                <input 
                                    type="hidden"
                                    name="smartCollectionCondition"
                                    value="0"
                                />
                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="smartCollectionCondition"
                                    value="1"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('shopify::app.shopify.metafield.index.storefronts')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.label v-text="storefronts">
                                </x-admin::form.control-group.label>
                                <input 
                                    type="hidden"
                                    name="storefronts"
                                    value="0"
                                />
                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="storefronts"
                                    value="1"
                                    v-model="enableStorefronts"
                                    @change="togglenableStorefronts"
                                    :checked="true"
                                />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('shopify::app.shopify.credential.index.save')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-metafield', {
                template: '#v-metafield-template',
                data() {
                    return {
                        contenttypeSelect: null,  
                        attribute: "",
                        name_space_key: "",
                        contentTypeOptions:  [],
                        unitTypeOptions: [],
                        selectedType: "",
                        onevalue: 0,
                        metafieldType: @json($metaFieldType ?? null),
                        metaFieldTypeInShopify: @json($metaFieldTypeInShopify ?? null),
                        typeofminmx: null,
                        maxvalueLabel: null,
                        minvalueLabel: null,
                        adminFilterable: null,
                        smartCollectionCondition: null,
                        storefronts: 'Read',
                        contentTypeName: null,
                        key: null,
                        urlvalidation: false,
                        width: null,
                    };
                },
                methods: {
                    create(params, { setErrors }) {
                        let formData = new FormData(this.$refs.metafieldCreateForm);
                        var minCharslen = formData.get("minvalue") ?? null;
                        var maxCharslen = formData.get("maxvalue") ?? null;
                        if (this.contentTypeName) {
                            formData.set("ContentTypeName", this.contentTypeName);
                        }
                        if (((minCharslen && minCharslen.trim().length > 0) || (maxCharslen && maxCharslen.trim().length > 0)) && 
                        this.typeofminmx != 'date'
                    )   {
                            var jsErrors = {};

                            if (minCharslen && !/^\d+$/.test(minCharslen)) {
                                jsErrors['minvalue'] = 'Only Number Allowed';
                            } else {
                                const minLimit = this.contentTypeName == 'Rating' ? 9999999999999 : 9007199254740991;
                                if (BigInt(minCharslen) >= minLimit) {
                                    jsErrors['minvalue'] = `Validation value for min can't exceed ${minLimit}`;
                                }
                            }
                            

                            if (maxCharslen && !/^\d+$/.test(maxCharslen)) {
                                jsErrors['maxvalue'] = 'Only Number Allowed';
                            } else {
                                const maxLimit = this.contentTypeName == 'Rating' ? 9999999999999 : 9007199254740991;
                                if (BigInt(maxCharslen) >= maxLimit) {
                                    jsErrors['maxvalue'] = `Validation value for min can't exceed ${maxLimit}`;
                                }
                            }
                            

                            if (jsErrors && Object.keys(jsErrors).length > 0) {
                                setErrors(jsErrors);
                                return;
                            }
                        }
                        this.$axios.post("{{ route('shopify.metafield.store') }}", formData)
                            .then((response) => {
                                window.location.href = response.data.redirect_url;
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    handleownerTypeChnage(event) {
                        if (event && typeof event === 'string' || event instanceof String) {
                            this.ownerType = JSON.parse(event)?.id;
                            this.adminFilterable = this.ownerType == 'PRODUCT' ? 1 : null;
                        }
                    },

                    handleSelectChange(event, FieldType) {
                        if (event && typeof event === 'string' || event instanceof String) {
                            var parsedEvent = JSON.parse(event);
                            var attrType = (parsedEvent?.validation && attrType !== '' ) ? parsedEvent?.validation : parsedEvent?.type;
                            this.contentTypeOptions = this.metafieldType[attrType] ?? [];
                            this.attribute = parsedEvent?.label ?? parsedEvent?.code;
                            this.name_space_key = 'custom.'+parsedEvent?.code;
                            this.selectedType = "";
                            this.$refs.mediaAttributes.selectedValue = [];
                        }
                    },
                    handleContentTypeChnage(event, FieldType) {
                        if (event && typeof event === 'string' && !event.includes('[]')) {
                            var parsedEvent = JSON.parse(event);
                            this.enableTagsAttribute = 0;
                            var key = parsedEvent?.id;
                            this.key = key;
                            this.contentTypeName = parsedEvent?.name;
                            var contentTypeData = this.metaFieldTypeInShopify[key];
                            this.contenttypeSelect = contentTypeData?.list ? 1 : 0;
                            if (parsedEvent?.id === 'single_line_text_field' || parsedEvent?.id === 'number_integer' || parsedEvent?.id == 'number_decimal' || parsedEvent?.id === 'multi_line_text_field' || parsedEvent?.id === 'rating' || parsedEvent?.id == 'dimension' || parsedEvent?.id == 'volume' || parsedEvent?.id == 'weight') {
                                this.typeofminmx = "text";
                            } else if (parsedEvent?.id === 'date') {
                                this.typeofminmx = "date";
                            } else {
                                this.typeofminmx = null;
                            }

                            if (contentTypeData?.unitoptions) {
                                this.width = 'w-[360px]';
                                this.unitTypeOptions = contentTypeData?.unitoptions;
                                if (this.$refs.minunitvalue) {
                                    this.$refs.minunitvalue.selectedValue = [];
                                }
                                if (this.$refs.maxunitvalue) {
                                    this.$refs.maxunitvalue.selectedValue = [];
                                }

                            } else {
                                this.width = null;
                                this.unitTypeOptions = [];
                            }

                            this.urlvalidation = parsedEvent?.id === 'url' ? true : false;

                            this.minvalueLabel = contentTypeData?.validation?.min ?? null;
                            this.maxvalueLabel = contentTypeData?.validation?.max ?? null;
                            this.adminFilterable = (contentTypeData?.adminFilterable && this.ownerType == 'PRODUCT') ? 1 : null;
                            this.smartCollectionCondition = contentTypeData?.smartCollectionCondition ? 1 : null;
                            if (this.metaFieldTypeInShopify[this.key]?.listvalue?.smartCollectionCondition != undefined) {
                                this.smartCollectionCondition = this.onevalue == 0 ? 1 : null
                            }
                        }
                    },

                    toggleOneValue(event) {
                        if (this.metaFieldTypeInShopify[this.key]?.listvalue?.smartCollectionCondition != undefined) {
                            this.onevalue = event.target.value;
                            var check = (this.metaFieldTypeInShopify[this.key]?.listvalue?.smartCollectionCondition == true && event.target.value == 1) ? false : true;
                            
                            this.smartCollectionCondition = check;
                        }
                    },
                    togglenableStorefronts() {
                        this.storefronts = this.enableStorefronts ? 'Read' : 'No access';
                    },
                    toggleUnique(event) {
                        console.log(event.target.value);
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
