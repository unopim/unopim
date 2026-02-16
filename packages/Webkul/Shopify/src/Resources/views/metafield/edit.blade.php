<x-admin::layouts.with-history>
    <x-slot:entityName>
        shopify_meta_fields
    </x-slot>

    <x-slot:title>
        @lang('shopify::app.shopify.metafield.index.title')
    </x-slot>
    
    <x-admin::form  
        :action="route('shopify.metafield.update', ['id' => $metaField->id])"
    >
        @method('PUT')

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('shopify::app.shopify.metafield.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('shopify.metafield.index') }}"
                    class="transparent-button"
                >
                    @lang('shopify::app.shopify.metafield.edit.back-btn')
                </a>

                <button 
                    type="submit" 
                    class="primary-button"
                    aria-label="Submit"
                >
                    @lang('shopify::app.shopify.metafield.edit.save')
                </button>
            </div>
        </div>

        <!-- body content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <v-metafield></v-metafield>
            </div>
            </div>
        </div>
    </x-admin::form>
    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-metafield-template"
        >
                            <!-- Defintion Type -->
                <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label class="required">
                        @lang('shopify::app.shopify.metafield.index.definitiontype')
                    </x-admin::form.control-group.label>
                        @php
                        $storefronts = $metaField?->storefronts ? 'Read' : 'No access';
                        $typeofminmx = $metaField?->type == 'date' ? 'date' : 'text';
                        $listValue = $metaFieldTypeInShopify[$metaField?->type]['list'] ?? null;
                        $width = $metaFieldTypeInShopify[$metaField?->type]['unitoptions'] ?? null;
                        $smartCollectionCondition = $metaFieldTypeInShopify[$metaField?->type]['smartCollectionCondition'] ?? null;
                        if (isset($metaFieldTypeInShopify[$metaField?->type]['listvalue']['smartCollectionCondition'])) {
                            $smartCollectionCondition = $metaFieldTypeInShopify[$metaField?->type]['listvalue']['smartCollectionCondition'] ? null : $metaFieldTypeInShopify[$metaField?->type]['listvalue']['smartCollectionCondition'];
                        }

                        if (in_array($metaField?->type, ['rating', 'number_decimal', 'number_integer']) && !$metaField->listvalue) {
                            $smartCollectionCondition = true;
                        }
                        
                        $adminFilterable = (($metaFieldTypeInShopify[$metaField?->type]['adminFilterable'] ?? null) && $metaField?->ownerType == 'PRODUCT') ? true : null;
                        
                        $minvalueLabel = $metaFieldTypeInShopify[$metaField?->type]['validation']['min'] ?? null;
                        $maxvalueLabel = $metaFieldTypeInShopify[$metaField?->type]['validation']['max'] ?? null;
                        $options = null;
                        if ($metaField?->options) {
                            $options = json_decode($metaField->options);
                        }
                        $validations = null;
                        if ($metaField?->validations) {
                            $validations = json_decode($metaField->validations);
                        }
                        
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
                        $one = false;
                        $list = true;
                        if (!$metaField->listvalue){
                            $one = true;
                            $list = false;
                        }
                         
                        @endphp
                    <x-admin::form.control-group.control
                        type="select"
                        id="ownerType"
                        name="ownerType"
                        rules="required"
                        disabled="disabled"
                        :value="$metaField?->ownerType"
                        :options="$metaType"
                        :label="trans('shopify::app.shopify.metafield.index.title')"
                        :placeholder="trans('shopify::app.shopify.metafield.index.title')"
                        track-by="id"
                        label-by="name"
                    />

                    <x-admin::form.control-group.error control-name="ownerType"/>
                </x-admin::form.control-group>

                <!-- Unopim Attribute -->
                <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label class="required">
                        @lang('shopify::app.shopify.metafield.index.attribute')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control class="metafieldCode"
                        type="text"
                        id="code"
                        name="code"
                        rules="required"
                        :value="$metaField?->code"
                        readonly="readonly"
                        :label="trans('shopify::app.shopify.metafield.index.attribute')" 
                        :entityName="json_encode($attributeType)"
                        :placeholder="trans('shopify::app.shopify.metafield.index.attribute')"
                    />

                    <x-admin::form.control-group.error control-name="code" />
                </x-admin::form.control-group>


                <!-- Content Type -->
                <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label class="required">
                        @lang('shopify::app.shopify.metafield.index.ContentTypeName')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="ContentTypeName"
                        name="ContentTypeName"
                        disabled="disabled"
                        :value="!empty($metaField?->ContentTypeName) ? $metaField?->ContentTypeName : $metaField?->type"
                        :label="trans('shopify::app.shopify.metafield.index.ContentTypeName')"
                        :placeholder="trans('shopify::app.shopify.metafield.index.ContentTypeName')"
                    />

                    <x-admin::form.control-group.error control-name="type"/>

                    <x-admin::form.control-group.control
                        type="hidden"
                        id="type"
                        name="type"
                        :value="$metaField?->type"
                    />
                </x-admin::form.control-group>

                @if ($metaField?->type == 'url')
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('shopify::app.shopify.metafield.index.urlvalidation')
                        </x-admin::form.control-group.label>
                        <x-admin::form.control-group.label>
                                <span class="icon-information text-lg"></span> <p class="break-words text-xs text-gray-500 dark:text-gray-400"> @lang('shopify::app.shopify.metafield.index.urlvalidationdata')</p>
                        </x-admin::form.control-group.label>
                    </x-admin::form.control-group>
                @endif
                @if ($listValue)
                    <div class="flex items-center gap-4">
                        <x-admin::form.control-group class="{{ !(bool) $one ? 'opacity-25' : '' }}">
                            <x-admin::form.control-group.control
                                type="radio"
                                id="is_unique_onevalue"
                                name="is_unique"
                                value="0"
                                for="is_unique_onevalue"
                                :checked="(boolean) $one"
                                disabled="disabled"
                            />

                            <x-admin::form.control-group.label
                                for="is_unique_onevalue"
                            >
                                One value
                            </x-admin::form.control-group.label>
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="{{ !(bool) $list ? 'opacity-25' : '' }}">
                            <x-admin::form.control-group.control
                                type="radio"
                                id="is_unique_listvalue"
                                name="is_unique"
                                value="1"
                                for="is_unique_listvalue"
                                @change="toggleOneValue($event)"
                                :checked="(boolean) $list"
                                disabled="disabled"
                            />

                            <x-admin::form.control-group.label
                                for="is_unique_listvalue"
                            >
                                List of values
                            </x-admin::form.control-group.label>
                        </x-admin::form.control-group>
                    </div>
                @endif

                <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label class="required">
                        @lang('shopify::app.shopify.metafield.index.attributes')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="attribute"
                        name="attribute"
                        rules="required"
                        :value="old('attribute') ?? $metaField->attribute"
                        :label="trans('shopify::app.shopify.metafield.index.attribute')"
                        :placeholder="trans('shopify::app.shopify.metafield.index.attribute')"
                    />

                    <x-admin::form.control-group.error control-name="attribute"/>
                </x-admin::form.control-group>
                <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label class="required">
                        @lang('shopify::app.shopify.metafield.index.name_space_key')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="name_space_key"
                        name="name_space_key"
                        :value="old('name_space_key') ?? $metaField->name_space_key"
                        disabled="disabled"
                        :label="trans('shopify::app.shopify.metafield.index.name_space_key')"
                        :placeholder="trans('shopify::app.shopify.metafield.index.name_space_key')"
                    />

                    <x-admin::form.control-group.error control-name="name_space_key"/>
                </x-admin::form.control-group>
                <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label>
                        @lang('shopify::app.shopify.metafield.index.description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="description"
                        name="description"
                        :value="old('description') ?? $metaField->description"
                        :label="trans('shopify::app.shopify.metafield.index.description')"
                        :placeholder="trans('shopify::app.shopify.metafield.index.description')"
                    />

                    <x-admin::form.control-group.error control-name="description"/>
                </x-admin::form.control-group>
                @if ($typeofminmx == 'text' && $minvalueLabel)
                <div>
                    <div :class="{ 'flex items-center gap-2':  width != null }">
                    @php
                        $widthClass = $width != null ? 'w-[360px]' : 'w-[525px]';
                    @endphp
                        <x-admin::form.control-group class="{{ $widthClass }}">
                            <x-admin::form.control-group.label>
                                    @lang($minvalueLabel)
                            </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text"
                                    id="minvalue"
                                    name="minvalue"
                                    :label="$minvalueLabel"
                                    :placeholder="$minvalueLabel"
                                    :value="old('minvalue') ?? $validations?->min ?? null"
                                />

                            <x-admin::form.control-group.error control-name="minvalue"/>
                        </x-admin::form.control-group>
                        @if ($width != null)
                        <x-admin::form.control-group class="w-[170px] mt-5">
                            <x-admin::form.control-group.control
                                type="select"
                                id="minunit"
                                name="minunit"
                                track-by="id"
                                label-by="name"
                                :value="old('minunit') ?? $validations?->minunit ?? null"
                                :options="json_encode($width, true)"
                                label="min unit"
                                placeholder="min unit"
                            />
                            <x-admin::form.control-group.error control-name="minunit"/>
                        </x-admin::form.control-group>
                        @endif
                    </div>
                    <div :class="{ 'flex items-center gap-2':  width != null }">
                        <x-admin::form.control-group class="{{ $widthClass }}">
                            <x-admin::form.control-group.label>
                                @lang($maxvalueLabel)
                            </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text"
                                    id="maxvalue"
                                    name="maxvalue"
                                    :label="$maxvalueLabel"
                                    :placeholder="$maxvalueLabel"
                                    :value="old('maxvalue') ?? $validations?->max ?? null"
                                />

                                <x-admin::form.control-group.error control-name="maxvalue"/>
                        </x-admin::form.control-group>
                        @if ($width != null)
                        <x-admin::form.control-group class="w-[170px] mt-5">
                            <x-admin::form.control-group.control
                                type="select"
                                id="maxunit"
                                name="maxunit"
                                track-by="id"
                                label-by="name"
                                :options="json_encode($width, true)"
                                :value="old('maxunit') ?? $validations?->maxunit ?? null"
                                label="max unit"
                                placeholder="max unit"
                            />
                            <x-admin::form.control-group.error control-name="maxunit"/>
                        </x-admin::form.control-group>
                        @endif
                    </div>
                </div>
                @endif
                @if ($typeofminmx == 'date' && $minvalueLabel)
                <div>
                    <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label>
                        @lang($minvalueLabel)
                    </x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="date"
                            id="minvalue"
                            name="minvalue"
                            :label="$minvalueLabel"
                            :placeholder="$minvalueLabel"
                            :value="old('minvalue') ?? $validations?->min ?? null"
                        />

                        <x-admin::form.control-group.error control-name="minvalue"/>
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="w-[525px]">
                    <x-admin::form.control-group.label>
                            @lang($maxvalueLabel)
                    </x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="date"
                            id="maxvalue"
                            name="maxvalue"
                            :label="$maxvalueLabel"
                            :placeholder="$maxvalueLabel"
                            :value="old('maxvalue') ?? $validations?->max ?? null"
                        />

                        <x-admin::form.control-group.error control-name="maxvalue"/>
                    </x-admin::form.control-group>
                </div>
                @endif
                <x-admin::form.control-group class="w-[525px]">
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
                        :checked="(boolean) $metaField->pin"
                    />

                    <x-admin::form.control-group.error control-name="pin"/>
                </x-admin::form.control-group>

                @if ($adminFilterable)
                <x-admin::form.control-group>
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
                        :checked="(boolean) $options?->adminFilterable"
                    />
                </x-admin::form.control-group>
                @endif
                @if ($smartCollectionCondition)
                <x-admin::form.control-group>
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
                        :checked="(boolean) $options?->smartCollectionCondition"
                    />
                </x-admin::form.control-group>
                @endif
                <x-admin::form.control-group class="w-[525px]">
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
                        :checked="(boolean) $metaField?->storefronts"
                    />
                </x-admin::form.control-group>
        </script>
        <script type="module">
            app.component('v-metafield', {
                template: '#v-metafield-template',
                data() {
                    return {
                        storefronts: @json($storefronts ?? null),
                        width: @json($width ?? null),
                    };
                },
                methods: {
                    togglenableStorefronts() {
                        this.storefronts = this.enableStorefronts ? 'Read' : 'No access';
                    }
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
