<x-admin::layouts.with-history>
    <x-slot:entityName>
        shopify_exportmapping
    </x-slot>
    <x-slot:title>
        @lang('shopify::app.shopify.export.setting.title')
    </x-slot>
    <v-create-attributes-mappings></v-create-attributes-mappings>
    @pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-create-attributes-mapping-template"
    >
    <x-admin::form  
        :action="route('shopify.export-settings.create', 2)" 
    >
    @method('POST')
    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('shopify::app.shopify.export.setting.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <a
                href="{{ route('shopify.credentials.index') }}"
                class="transparent-button"
            >
                @lang('shopify::app.shopify.credential.edit.back-btn')
            </a>

            <button 
                type="submit" 
                class="primary-button"
                aria-label="Submit"
            >
                @lang('shopify::app.shopify.credential.edit.save')
            </button>
        </div>
    </div>
    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('shopify::app.shopify.export.setting.tags')
                </p>
                 
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('shopify::app.shopify.export.setting.enable_named_tags_attribute')
                    </x-admin::form.control-group.label>
                    <input 
                        type="hidden"
                        name="enable_named_tags_attribute"
                        value="0"
                    />
                    <x-admin::form.control-group.control
                        type="switch"
                        name="enable_named_tags_attribute"
                        value="1"
                        v-model="enableNamedTagsAttribute"
                    />
                </x-admin::form.control-group>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('shopify::app.shopify.export.setting.enable_tags_attribute')
                    </x-admin::form.control-group.label>
                    <input 
                        type="hidden"
                        name="enable_tags_attribute"
                        value="0"
                    />
                    <x-admin::form.control-group.control
                        type="switch"
                        name="enable_tags_attribute"
                        value="1"
                        v-model="enableTagsAttribute"
                        
                    />
                </x-admin::form.control-group>
                <x-admin::form.control-group class="mb-4" v-if=" (selectedAttributeType == 1)">
                    <x-admin::form.control-group.label >
                        @lang('shopify::app.shopify.export.setting.tagSeprator')
                    </x-admin::form.control-group.label>

                    @php
                        $tagSeprator = [
                            [ 
                                'id'   => 'colon',
                                'name' => '(:) Colon',
                            ], [ 
                                'id'   => 'dash',
                                'name' => '(-) Dash',
                            ], [ 
                                'id'   => 'space',
                                'name' => '( ) Space',
                            ],
                        ];
                        $tagSeprator = json_encode($tagSeprator, true);
                        $selectedOption = $shopifySettings->mapping['tagSeprator'] ?? 'colon';

                    @endphp

                    <x-admin::form.control-group.control
                        type="select"
                        id="tagSeprator"
                        name="tagSeprator"
                        :label="trans('shopify::app.shopify.export.setting.tagSeprator')"
                        :placeholder="trans('shopify::app.shopify.export.setting.tagSeprator')"
                        :options="$tagSeprator"
                        :value="$selectedOption"
                        rules="required"
                        track-by="id"
                        label-by="name"
                    />

                    <x-admin::form.control-group.error control-name="tagSeprator" />
                </x-admin::form.control-group>
            </div>
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('shopify::app.shopify.export.setting.other-settings')
                </p>
                 
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('shopify::app.shopify.export.setting.option_name_label')
                    </x-admin::form.control-group.label>
                    <input 
                        type="hidden"
                        name="option_name_label"
                        value="0"
                    />

                    <x-admin::form.control-group.control
                        type="switch"
                        name="option_name_label"
                        value="1"
                        :checked="$shopifySettings->mapping['option_name_label'] ?? false"
                    />
                </x-admin::form.control-group>
            </div>
        </div>
    </div>
    </x-admin::form> 
    </script>
    <script type="module">
    app.component('v-create-attributes-mappings', {
        template: '#v-create-attributes-mapping-template',
        data() {
            return {
                selectedAttributeType: @json($shopifySettings->mapping['enable_tags_attribute'] ?? ''),  
                enableTagsAttribute: @json($shopifySettings->mapping['enable_tags_attribute'] ?? false),  
                enableNamedTagsAttribute: @json($shopifySettings->mapping['enable_named_tags_attribute'] ?? false),
            };
        },
        watch: {
            enableTagsAttribute(newValue) {
                this.selectedAttributeType = newValue;
                if (newValue) {
                    this.enableNamedTagsAttribute = false; 
                }
            },

            enableNamedTagsAttribute(newValue) {
                if (newValue) {
                    this.enableTagsAttribute = false;
                }
            }
        }
    });
    </script>

    @endPushOnce
</x-admin::layouts.with-history>
