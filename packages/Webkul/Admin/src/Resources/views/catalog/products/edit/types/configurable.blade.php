{!! view_render_event('unopim.admin.catalog.product.edit.form.types.configurable.before', ['product' => $product]) !!}

<v-product-variations :errors="errors"></v-product-variations>

{!! view_render_event('unopim.admin.catalog.product.edit.form.types.configurable.after', ['product' => $product]) !!}

@pushOnce('scripts')
    <!-- Variations Template -->
    <script
        type="text/x-template"
        id="v-product-variations-template"
    >
        <div class="relative bg-white dark:bg-cherry-900 rounded box-shadow">
            <!-- Panel Header -->
            <div class="flex flex-wrap gap-2.5 justify-between mb-2.5 p-4">
                <div class="flex flex-col gap-2">
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.catalog.products.edit.types.configurable.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300 font-medium">
                        @lang('admin::app.catalog.products.edit.types.configurable.info')
                    </p>
                </div>
                
                <!-- Add Buttons -->
                <div class="flex gap-x-1 items-center justify-between w-full">
                    <div></div>
                    <div
                        class="secondary-button"
                        @click="$refs.variantCreateModal.open()"
                    >
                        @lang('admin::app.catalog.products.edit.types.configurable.add-btn')
                    </div>
                </div>
            </div>

            <template v-if="variants.length">
                <!-- Panel Content -->
                <div class="grid">
                    <v-product-variation-item
                        v-for='(variant, index) in variants'
                        :key="index"
                        :index="index"
                        :variant="variant"
                        :attributes="superAttributes"
                        @onRemoved="removeVariant"
                        :errors="errors"
                    >
                    </v-product-variation-item>
                </div>
            </template>

            <!-- For Empty Variations -->
            <template v-else>
                <div class="grid gap-3.5 justify-center justify-items-center py-10 px-2.5">
                    <!-- Placeholder Image -->
                    <img
                        src="{{ unopim_asset('images/icon-add-product.svg') }}"
                        class="w-20 h-20 dark:invert dark:mix-blend-exclusion"
                    />

                    <!-- Add Variants Information -->
                    <div class="flex flex-col gap-1.5 items-center">
                        <p class="text-base text-gray-400 font-semibold">
                            @lang('admin::app.catalog.products.edit.types.configurable.empty-title')
                        </p>

                        <p class="text-gray-400">
                            @lang('admin::app.catalog.products.edit.types.configurable.empty-info')
                        </p>
                    </div>
                </div>
            </template>

            <!-- Add Variant Form Modal -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form @submit="handleSubmit($event, addVariant)" ref="variantCreateForm">
                    <!-- Customer Create Modal -->
                    <x-admin::modal ref="variantCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.products.edit.types.configurable.create.title')
                            </p>
                        </x-slot>
        
                        <!-- Modal Content -->
                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    sku
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    id="sku"
                                    name="sku"
                                    rules="required"
                                />
                                <x-admin::form.control-group.error control-name="sku" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group
                                v-for='(attribute, index) in superAttributes'
                            >
                                <x-admin::form.control-group.label class="required">
                                    @{{ attribute.name }}
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    ::id="attribute.code"
                                    ::name="attribute.code"
                                    rules="required"
                                    ::label="attribute.name"
                                    track-by="code"
                                    async="true"
                                    entity-name="attribute"
                                    ::attribute-id="attribute.id"
                                >
                                </x-admin::form.control-group.control>

                                <v-error-message :name="attribute.code" v-slot="{ message }">
                                    <p
                                        class="mt-1 text-red-600 text-xs italic"
                                        v-text="message"
                                    >
                                    </p>
                                </v-error-message>
                            </x-admin::form.control-group>
                        </x-slot>
        
                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <!-- Modal Submission -->
                            <div class="flex gap-x-2.5 items-center">
                                <button 
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('admin::app.catalog.products.edit.types.configurable.create.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <!-- Variations Mass Action Template -->
    <script type="text/x-template" id="v-product-variations-mass-action-template">
        <!-- Mass Actions -->
        <div class="flex gap-1.5 items-center px-4">
            <span
                class="flex icon-checkbox-normal text-2xl cursor-pointer select-none"
                :class="{
                    '!icon-checkbox-check text-violet-700': variants.length == selectedVariants.length,
                    '!icon-checkbox-partial text-violet-700': selectedVariants.length && variants.length != selectedVariants.length
                }"
                for="select-all-variants"
                @click="selectAll"
            >
            </span>

            <!-- Edit Drawer -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form @submit="handleSubmit($event, updateAll)">
                    <!-- Edit Drawer -->
                    <x-admin::drawer
                        ref="updateVariantsDrawer"
                        class="text-left"
                    >
                        <!-- Drawer Header -->
                        <x-slot:header>
                            <div class="flex justify-between items-center">
                                <p class="text-xl font-medium dark:text-white">
                                    @{{ updateTypes[selectedType].title }}
                                </p>

                                <button
                                    class="ltr:mr-11 rtl:ml-11 primary-button"
                                    type="submit"
                                >
                                    @lang('admin::app.catalog.products.edit.types.configurable.edit.save-btn')
                                </button>
                            </div>
                        </x-slot>

                        <!-- Drawer Content -->
                        <x-slot:content class="p-4">
                            <x-admin::form
                                v-slot="{ meta, errors, handleSubmit }"
                                as="div"
                            >
                                <form @submit="handleSubmit($event, update)">
                                    <!-- Mass Update -->
                                    <template v-if="selectedType == 'editPrices'">
                                        <div class="pb-2.5 border-b dark:border-gray-800">
                                            <div class="flex gap-2.5 items-end">
                                                <x-admin::form.control-group class="flex-1 !mb-0">
                                                    <x-admin::form.control-group.label>
                                                        @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-sku')
                                                    </x-admin::form.control-group.label>
                        
                                                    <div class="relative">
                                                        <span class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 text-gray-500">
                                                            {{ core()->currencySymbol(core()->getBaseCurrencyCode()) }}
                                                        </span>

                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            class="ltr:pl-8 rtl:pr-8"
                                                            name="price"
                                                            ::rules="{required: true, decimal: true, min_value: 0}"
                                                            :label="trans('admin::app.catalog.products.edit.types.configurable.mass-edit.price')"
                                                        />
                                                    </div>
                                                </x-admin::form.control-group>

                                                <button class="secondary-button">
                                                    @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-btn')
                                                </button>
                                            </div>
                    
                                            <x-admin::form.control-group.error control-name="price" />
                                        </div>
                                    </template>


                                    <template v-if="selectedType == 'addImages'">
                                        <div class="pb-2.5 border-b dark:border-gray-800">
                                            <v-media-images
                                                name="images"
                                                class="mb-2.5"
                                                v-bind:allow-multiple="true"
                                                :uploaded-images="updateTypes[selectedType].images"
                                            >
                                            </v-media-images>

                                            <button class="secondary-button">
                                                @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-btn')
                                            </button>
                                        </div>
                                    </template>

                                    <template v-if="selectedType == 'editWeight'">
                                        <div class="pb-2.5 border-b dark:border-gray-800">
                                            <div class="flex gap-2.5 items-end">
                                                <x-admin::form.control-group class="flex-1 !mb-0">
                                                    <x-admin::form.control-group.label>
                                                        @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-weight')
                                                    </x-admin::form.control-group.label>
                        
                                                    <div class="relative">
                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            name="weight"
                                                            ::rules="{ required: true, regex: /^([0-9]*[1-9][0-9]*(\.[0-9]+)?|[0]+\.[0-9]*[1-9][0-9]*)$/ }"
                                                            value="0"
                                                            :label="trans('admin::app.catalog.products.edit.types.configurable.mass-edit.weight')"
                                                        />
                                                    </div>
                                                </x-admin::form.control-group>

                                                <button class="secondary-button">
                                                    @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-btn')
                                                </button>
                                            </div>
                    
                                            <x-admin::form.control-group.error control-name="weight" />
                                        </div>
                                    </template>

                                    <template v-if="selectedType == 'editName'">
                                        <div class="pb-2.5 border-b dark:border-gray-800">
                                            <div class="flex gap-2.5 items-end">
                                                <x-admin::form.control-group class="flex-1 !mb-0">
                                                    <x-admin::form.control-group.label>
                                                        @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-name')
                                                    </x-admin::form.control-group.label>

                                                    <div class="relative">
                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            name="name"
                                                            ::rules="{ required: true }"
                                                            :label="trans('admin::app.catalog.products.edit.types.configurable.mass-edit.name')"
                                                        />
                                                    </div>
                                                </x-admin::form.control-group>

                                                <button class="secondary-button">
                                                    @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-btn')
                                                </button>
                                            </div>
                    
                                            <x-admin::form.control-group.error control-name="name" />
                                        </div>
                                    </template>

                                    <template v-if="selectedType == 'editStatus'">
                                        <div class="pb-2.5 border-b dark:border-gray-800">
                                            <div class="flex gap-2.5 items-end">
                                                <x-admin::form.control-group class="flex-1 !mb-0">
                                                    <x-admin::form.control-group.label>
                                                        @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-status')
                                                    </x-admin::form.control-group.label>
                                                    @php
                                                        $formattedOptions = [
                                                            [
                                                                'id'    => 0,
                                                                'label' => trans('admin::app.catalog.products.edit.types.configurable.edit.enabled'),
                                                            ], [
                                                                'id'    => 1,
                                                                'label' => trans('admin::app.catalog.products.edit.types.configurable.edit.disabled'),
                                                            ]
                                                        ];
                                                    @endphp
                                                    <div class="relative">
                                                        <x-admin::form.control-group.control
                                                            type="select"
                                                            name="status"
                                                            :options="json_encode($formattedOptions)"
                                                            ::rules="{ required: true }"
                                                            :label="trans('admin::app.catalog.products.edit.types.configurable.mass-edit.status')"
                                                        >
                                                        </x-admin::form.control-group.control>
                                                    </div>
                                                </x-admin::form.control-group>

                                                <button class="secondary-button">
                                                    @lang('admin::app.catalog.products.edit.types.configurable.mass-edit.apply-to-all-btn')
                                                </button>
                                            </div>
                    
                                            <x-admin::form.control-group.error control-name="name" />
                                        </div>
                                    </template>
                                </form>
                            </x-admin::form>

                            <div
                                class="py-4 border-b dark:border-cherry-800 last:border-b-0"
                                :class="{'grid grid-cols-2 gap-3 justify-between items-center': [
                                        'editName', 'editSku',
                                ].includes(selectedType), 'flex justify-between items-center' : [
                                    'editWeight', 'editPrices', 'editStatus',
                                ].includes(selectedType)}"
                                v-for="variant in tempSelectedVariants"
                            >
                                <div class="text-sm text-gray-800">
                                    <span
                                        class="dark:text-white after:content-['_/_'] last:after:content-['']"
                                        v-for='(attribute, index) in superAttributes'
                                    >
                                        @{{ optionName(attribute, variant?.values?.common[attribute.code]) }}
                                    </span>
                                </div>

                                <template v-if="selectedType == 'editPrices'">
                                    <x-admin::form.control-group class="flex-1 mb-0 max-w-[115px]">
                                        <div class="relative">
                                            <span class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 text-gray-500">
                                                {{ core()->currencySymbol(core()->getBaseCurrencyCode()) }}
                                            </span>

                                            <v-field
                                                type="text"
                                                class="flex w-full min-h-[39px] py-1.5 ltr:pl-8 rtl:pr-8 bg-white dark:bg-cherry-800 border dark:border-cherry-800 rounded-md text-sm text-gray-600 dark:text-gray-300 font-normal transition-all hover:border-gray-400"
                                                :class="[errors['variants[variant_' + variant.id + ']'] ? 'border border-red-500' : '']"
                                                :name="'variants[variant_' + variant.id + ']'"
                                                :rules="{required: true, decimal: true, min_value: 0}"
                                                v-model="variant.price"
                                                label="@lang('admin::app.catalog.products.edit.types.configurable.mass-edit.price')"
                                            >
                                            </v-field>
                                        </div>

                                        <v-error-message
                                            :name="'variants[variant_' + variant.id + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-red-600 text-xs italic"
                                                v-text="message"
                                            >
                                            </p>
                                        </v-error-message>
                                    </x-admin::form.control-group>
                                </template>

                                <template v-if="selectedType == 'editWeight'">
                                    <x-admin::form.control-group class="flex-1 mb-0 max-w-[115px]">
                                        <div class="relative">
                                            <v-field
                                                type="text"
                                                class="flex w-full min-h-[39px] py-1.5 ltr:pl-2.5 rtl:pr-2.5 bg-white dark:bg-cherry-800  border dark:border-cherry-800   rounded-md text-sm text-gray-600 dark:text-gray-300 font-normal transition-all hover:border-gray-400"
                                                :class="[errors['variants[variant_' + variant.id + ']'] ? 'border border-red-500' : '']"
                                                :name="'variants[variant_' + variant.id + ']'"
                                                ::rules="{ required: true, regex: /^([0-9]*[1-9][0-9]*(\.[0-9]+)?|[0]+\.[0-9]*[1-9][0-9]*)$/ }"
                                                v-model="variant.weight"
                                                label="@lang('admin::app.catalog.products.edit.types.configurable.mass-edit.weight')"
                                            >
                                            </v-field>
                                        </div>

                                        <v-error-message
                                            :name="'variants[variant_' + variant.id + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-red-600 text-xs italic"
                                                v-text="message"
                                            >
                                            </p>
                                        </v-error-message>
                                    </x-admin::form.control-group>
                                </template>

                                <template v-if="selectedType == 'editStatus'">
                                    <x-admin::form.control-group class="flex-1 mb-0 max-w-[115px]">
                                        <div class="relative">
                                            <v-field
                                                as="select"
                                                class="custom-select flex w-full min-h-[39px] py-1.5 px-3 bg-white dark:bg-cherry-800 border dark:border-cherry-800 rounded-md text-sm text-gray-600 dark:text-gray-300 font-normal transition-all hover:border-gray-400"
                                                :class="[errors['variants[variant_' + variant.id + ']'] ? 'border border-red-500' : '']"
                                                :name="'variants[variant_' + variant.id + ']'"
                                                ::rules="{ required: true, regex: /^([0-9]*[1-9][0-9]*(\.[0-9]+)?|[0]+\.[0-9]*[1-9][0-9]*)$/ }"
                                                v-model="variant.status"
                                                label="@lang('admin::app.catalog.products.edit.types.configurable.edit.enabled')"
                                            >
                                                <option value="1">
                                                    @lang('admin::app.catalog.products.edit.types.configurable.edit.enabled')
                                                </option>

                                                <option value="0">
                                                    @lang('admin::app.catalog.products.edit.types.configurable.edit.disabled')
                                                </option>
                                            </v-field>
                                        </div>

                                        <v-error-message
                                            :name="'variants[variant_' + variant.id + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-red-600 text-xs italic"
                                                v-text="message"
                                            >
                                            </p>
                                        </v-error-message>
                                    </x-admin::form.control-group>
                                </template>

                                <template v-if="selectedType == 'editName'">
                                    <x-admin::form.control-group 
                                        class="flex-1 mb-0"
                                        ::class="{ 
                                            'max-w-[115px]' : selectedType !== 'editName',
                                            '!mb-0': selectedType === 'editName'
                                        }"
                                    >
                                        <div class="relative">
                                            <v-field
                                                type="text"
                                                class="flex w-full min-h-[39px] py-1.5 ltr:pl-2.5 rtl:pr-2.5 bg-white dark:bg-cherry-800  border dark:border-cherry-800   rounded-md text-sm text-gray-600 dark:text-gray-300 font-normal transition-all hover:border-gray-400"
                                                :class="[errors['variants[variant_' + variant.id + ']'] ? 'border border-red-500' : '']"
                                                :name="'variants[variant_' + variant.id + ']'"
                                                ::rules="{ required: true, regex: /^([0-9]*[1-9][0-9]*(\.[0-9]+)?|[0]+\.[0-9]*[1-9][0-9]*)$/ }"
                                                v-model="variant.name"
                                                label="@lang('admin::app.catalog.products.edit.types.configurable.edit.variant-name')"
                                            >
                                            </v-field>
                                        </div>

                                        <v-error-message
                                            :name="'variants[variant_' + variant.id + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-red-600 text-xs italic"
                                                v-text="message"
                                            >
                                            </p>
                                        </v-error-message>
                                    </x-admin::form.control-group>
                                </template>

                                <template v-if="selectedType == 'editSku'">
                                    <x-admin::form.control-group 
                                        class="flex-1 mb-0"
                                        ::class="{ 
                                            'max-w-[115px]' : selectedType !== 'editSku',
                                            '!mb-0': selectedType === 'editSku'
                                        }"
                                    >
                                        <div class="relative">
                                            <v-field
                                                type="text"
                                                class="flex w-full min-h-[39px] py-1.5 ltr:pl-2.5 rtl:pr-2.5 bg-white dark:bg-cherry-800  border dark:border-cherry-800   rounded-md text-sm text-gray-600 dark:text-gray-300 font-normal transition-all hover:border-gray-400"
                                                :class="[errors['variants[variant_' + variant.id + ']'] ? 'border border-red-500' : '']"
                                                :name="'variants[variant_' + variant.id + ']'"
                                                ::rules="{ required: true, regex: /^([0-9]*[1-9][0-9]*(\.[0-9]+)?|[0]+\.[0-9]*[1-9][0-9]*)$/ }"
                                                v-model="variant.sku"
                                                label="@lang('admin::app.catalog.products.edit.types.configurable.edit.variant-sku')"
                                                v-slugify
                                            >
                                            </v-field>
                                        </div>

                                        <v-error-message
                                            :name="'variants[variant_' + variant.id + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-red-600 text-xs italic"
                                                v-text="message"
                                            >
                                            </p>
                                        </v-error-message>
                                    </x-admin::form.control-group>
                                </template>
                                
                                <template v-if="selectedType == 'addImages'">
                                    <v-media-images
                                        name="images"
                                        class="mt-2.5"
                                        v-bind:allow-multiple="true"
                                        :uploaded-images="variant.temp_images"
                                    >
                                    </v-media-images>
                                </template>
                            </div>
                        </x-slot>
                    </x-admin::drawer>
                </form>
            </x-admin::form>
        </div>
    </script>

    <!-- Variation Item Template -->
    <script type="text/x-template" id="v-product-variation-item-template"> 
        <div class="flex gap-2.5 justify-between px-4 py-6 border-b border-slate-300 dark:border-gray-800">

            <!-- Information -->
            <div class="flex gap-2.5">
                <!-- Form Hidden Fields -->
                <input
                    type="hidden"
                    :name="'variants[' + variant.id + '][sku]'"
                    :value="variant.sku"
                />

                <input
                    type="hidden"
                    :name="'variants[' + variant.id + '][values][common][sku]'"
                    :value="variant.sku"
                />

                <template v-for="attribute in attributes">
                    <input
                        type="hidden"
                        :name="'variants[' + variant.id + '][values][common][' + attribute.code + ']'"
                        :value="variant?.values?.common[attribute.code]"
                    />
                </template>

                <template v-for="(image, index) in variant.images">
                    <input type="hidden" :name="'variants[' + variant.id + '][images][files][' + image.id + ']'" v-if="! image.is_new"/>

                    <input
                        type="file"
                        :name="'variants[' + variant.id + '][images][files][]'"
                        :id="$.uid + '_imageInput_' + index"
                        class="hidden"
                        accept="image/*"
                        :ref="$.uid + '_imageInput_' + index"
                    />
                </template>
                <!-- //Ends Form Hidden Fields -->

                <!-- Image -->
                <div
                    class="w-full h-[60px] max-w-[60px] max-h-[60px] relative rounded overflow-hidden"
                    :class="{'border border-dashed border-gray-300 dark:border-cherry-800 dark:invert dark:mix-blend-exclusion': ! variant?.image, 'w-[60px]': variant?.image}"
                >
                    <template v-if="! variant?.image">
                        <img src="{{ unopim_asset('images/product-placeholders/front.svg') }}">

                        <p class="w-full absolute bottom-1.5 text-[6px] text-gray-400 text-center font-semibold">
                            @lang('admin::app.catalog.products.edit.types.configurable.image-placeholder')
                        </p>
                    </template>

                    <template v-else>
                        <img :src="variant?.image" class="w-full h-full object-cover object-top">
                    </template>
                </div>

                <!-- Details -->
                <div class="grid gap-1.5 place-content-start">
                    <p class="text-gray-600 dark:text-gray-300">
                        @{{ "@lang('admin::app.catalog.products.edit.types.configurable.sku')".replace(':sku', variant.sku) }}
                    </p>

                    <v-error-message
                        :name="'variants[' + variant.id + '].sku'"
                        v-slot="{ message }"
                    >
                        <p
                            class="mt-1 text-red-600 text-xs italic"
                            v-text="message"
                        >
                        </p>
                    </v-error-message>

                    <div class="flex gap-1.5 place-items-start">
                        <span
                            class="label-active"
                            v-if="isDefault"
                        >
                            Default
                        </span>

                        <p class="text-gray-600 dark:text-gray-300">
                            <span
                                class="after:content-[',_'] last:after:content-['']"
                                v-for='(attribute, index) in attributes'
                            >
                                @{{ attribute.name + ': ' + optionName(attribute, variant?.values?.common[attribute.code]) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="grid gap-1 place-content-start text-right">
                <div class="flex gap-2.5">
                    <!-- Remove -->

                    <i
                        class="icon-delete text-xl text-red-500 cursor-pointer"
                        @click="remove"
                        title="@lang('admin::app.catalog.products.index.datagrid.delete')"
                    ></i>

                    <!-- Edit -->
                    <div>
                        <p
                            class="text-emerald-600 cursor-pointer transition-all"
                            @click="$refs.editVariantDrawer.open()"
                            title="@lang('admin::app.catalog.products.index.datagrid.edit')"
                        >
                            <i class="icon-edit text-xl"></i>
                        </p>

                        <!-- Edit Drawer -->
                        <x-admin::form
                            v-slot="{ meta, errors, handleSubmit }"
                            as="div"
                        >
                            <form @submit="handleSubmit($event, update)" ref="editVariantForm">
                                <!-- Edit Drawer -->
                                <x-admin::drawer
                                    ref="editVariantDrawer"
                                    class="text-left"
                                >
                                    <!-- Drawer Header -->
                                    <x-slot:header>
                                        <div class="flex justify-between items-center">
                                            <p class="text-xl font-medium dark:text-white">
                                                @lang('admin::app.catalog.products.edit.types.configurable.edit.title')
                                            </p>

                                            <button class="ltr:mr-11 rtl:ml-11 primary-button">
                                                @lang('admin::app.catalog.products.edit.types.configurable.edit.save-btn')
                                            </button>
                                        </div>
                                    </x-slot>

                                    <!-- Drawer Content -->
                                    <x-slot:content>
                                        <x-admin::form.control-group.control
                                            type="hidden"
                                            name="id"
                                            ::value="variant.id"
                                        />
                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.types.configurable.edit.sku')
                                            </x-admin::form.control-group.label>
                
                                            <x-admin::form.control-group.control
                                                type="text"
                                                name="sku"
                                                rules="required"
                                                ::value="variant.sku"
                                                :label="trans('admin::app.catalog.products.edit.types.configurable.edit.sku')"
                                            />
                
                                            <x-admin::form.control-group.error control-name="sku" />
                                        </x-admin::form.control-group>


                                    <x-admin::form.control-group
                                        v-for='(attribute, index) in attributes'
                                    >
                                        <x-admin::form.control-group.label class="required">
                                            @{{ attribute.name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            ::id="attribute.code"
                                            ::name="attribute.code"
                                            rules="required"
                                            ::label="attribute.name"
                                            ::value="variant?.values?.common[attribute.code] ? {code: variant?.values?.common[attribute.code], label: optionName(attribute, variant?.values?.common[attribute.code])} : null"
                                            track-by="code"
                                            async="true"
                                            entity-name="attribute"
                                            ::attribute-id="attribute.id"
                                        >
                                        </x-admin::form.control-group.control>

                                        <v-error-message :name="attribute.code" v-slot="{ message }">
                                            <p
                                                class="mt-1 text-red-600 text-xs italic"
                                                v-text="message"
                                            >
                                            </p>
                                        </v-error-message>
                                    </x-admin::form.control-group>

                                        <!-- Actions -->
                                        <div
                                            class="mt-2.5 text-sm text-gray-800 dark:text-white font-semibold"
                                            v-if="typeof variant.id !== 'string'"
                                        >
                                            @lang('admin::app.catalog.products.edit.types.configurable.edit.edit-info')

                                            <a
                                                :href="'{{ route('admin.catalog.products.edit', ':id') }}'.replace(':id', variant.id)" 
                                                class="inline-block text-violet-700 hover:text-violet-700 hover:underline"
                                                target="_blank"
                                            >
                                                @lang('admin::app.catalog.products.edit.types.configurable.edit.edit-link-title')
                                            </a>
                                        </div>
                                    </x-slot>
                                </x-admin::drawer>
                            </form>
                        </x-admin::form>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-variations', {
            template: '#v-product-variations-template',

            props: ['errors'],

            data () {
                return {
                    defaultId: parseInt('{{ $product->additional['default_variant_id'] ?? null }}'),

                    variants: @json($product->variants()->with(['attribute_family'])->get()->map(fn ($item) => $item->normalizeWithImage())),

                    superAttributes: @json($product->super_attributes()->with(['options', 'options.attribute', 'options.translations'])->get()),

                    selectedVariant: {
                        id: null,
                        sku: '',
                        status: 1,
                    },
                }
            },

            methods: {
                async addVariant(params, { resetForm }) {
                    let formData = new FormData(this.$refs.variantCreateForm);

                    for (const key of formData.keys()) {
                        let formValue = formData.getAll(key);

                        params[key] = formValue.pop();
                    }

                    let filteredVariants = this.variants.filter((variant) => {
                        let matchCount = 0;

                        for (let key in params) {
                            if (variant?.values?.common[key] == params[key]) {
                                matchCount++;
                            }
                        }

                        return matchCount == this.superAttributes.length;
                    })

                    let configurableValues = {};

                    if (filteredVariants.length) {
                        this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.catalog.products.edit.types.configurable.create.variant-already-exists')" });

                        return;
                    }

                    for (const attribute of this.superAttributes) {
                        configurableValues[attribute.code] = params[attribute.code];
                    }

                    let checkValues = await this.checkVariantValues({sku: params.sku, variantAttributes: configurableValues});
                    let isUnique = await this.isUniqueVariant({sku: params.sku, variantAttributes: configurableValues});

                    if (! isUnique || ! checkValues) {
                        return;
                    }

                    const optionIds = Object.values(params);

                    this.variants.push(Object.assign({
                        id: 'variant_' + this.variants.length,
                        status: 1,
                        values: {
                            common: params
                        }
                    }, params));

                    resetForm();

                    this.$refs.variantCreateModal.close();
                },

                removeVariant(variant) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            this.variants.splice(this.variants.indexOf(variant), 1);
                        },
                    });
                },

                isUniqueVariant(params) {
                    params.parentId = {{ $product->id }};

                    return this.$axios.post("{{ route('admin.catalog.products.check-variant') }}", params)
                        .then((response) => {
                            if (response?.data?.errors?.message) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: response?.data?.errors?.message});

                                return false;
                            }

                            return true;
                        })
                        .catch(error => {
                            console.error(error);

                            return false;
                        });
                },

                checkVariantValues(params) {
                    return this.$axios.post("{{ route('admin.catalog.products.check-variant-values') }}", params)
                        .then((response) => {
                            if (response?.data?.errors?.message) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: response?.data?.errors?.message});

                                return false;
                            }

                            return true;
                        })
                        .catch(error => {
                            console.error(error);

                            return false;
                        });
                }
            }
        });

        app.component('v-product-variation-item', {
            template: '#v-product-variation-item-template',

            props: [
                'variant',
                'attributes',
                'errors',
            ],

            data() {
                return {
                    inventorySources: [],
                }
            },

            computed: {
                isDefault() {
                    return this.variant.id == this.$parent.defaultId;
                },
            },

            watch: {
                variant: {
                    handler(newValue) {
                        setTimeout(() => this.setFiles());
                    },
                    deep: true
                }
            },

            methods: {
                optionName(attribute, optionCode) {
                    return attribute.options.find((option) => {
                        return option.code == optionCode;
                    })?.label;
                },

                async update(params) {
                    let formData = new FormData(this.$refs.editVariantForm);

                    let configurableValues = {};

                    for (const key of formData.keys()) {
                        let formValue = formData.getAll(key);
                        params[key] = formValue.pop();
                    }

                    for (const attribute of this.attributes) {
                        configurableValues[attribute.code] = params[attribute.code];
                    }

                    let isUnique = await this.isUniqueVariant({sku: params.sku, variantAttributes: configurableValues, variantId: params.id.includes('variant_') ? null : params.id});

                    if (! isUnique) {
                        return;
                    }

                    Object.assign(this.variant, {
                        sku: params.sku,
                        values: {
                            common: params
                        }
                    });

                    this.$refs.editVariantDrawer.close();
                },

                setFiles() {
                    this.variant?.images?.forEach((image, index) => {
                        if (image.file instanceof File) {
                            image.is_new = 1;

                            const dataTransfer = new DataTransfer();

                            dataTransfer.items.add(image.file);

                            this.$refs[this.$.uid + '_imageInput_' + index][0].files = dataTransfer.files;
                        } else {
                            image.is_new = 0;
                        }
                    });
                },

                remove() {
                    this.$emit('onRemoved', this.variant);
                },

                isUniqueVariant(params) {
                    params.parentId = {{ $product->id }};

                    return this.$axios.post("{{ route('admin.catalog.products.check-variant') }}", params)
                        .then((response) => {
                            if (response?.data?.errors?.message) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: response?.data?.errors?.message});

                                return false;
                            }

                            return true;
                        })
                        .catch(error => {
                            console.error(error);

                            return false;
                        });
                },
            }
        });
    </script>
@endPushOnce
