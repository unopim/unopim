<x-admin::layouts.with-history>
    <x-slot:entityName>
        product
    </x-slot>
    <x-slot:title>
        @lang('admin::app.catalog.products.edit.title')
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.product.edit.before', ['product' => $product]) !!}

    <x-admin::form
        method="PUT"
        enctype="multipart/form-data"
    >
        {!! view_render_event('unopim.admin.catalog.product.edit.actions.before', ['product' => $product]) !!}

        <input type="hidden" name="sku" value="{{ $product->sku }}">

        <!-- Page Header -->
        <div class="grid gap-2.5">
            <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
                <div class="grid gap-1.5">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold leading-6">
                        @lang('admin::app.catalog.products.edit.title') | SKU: {{ $product->sku }}
                    </p>
                </div>

                <div class="flex gap-x-2.5 items-center">
                    {!! view_render_event('unopim.pdf.product.edit.actions.before', ['product' => $product]) !!}
                    <!-- Back Button -->
                    <a
                        href="{{ route('admin.catalog.products.index') }}"
                        class="transparent-button">
                        @lang('admin::app.account.edit.back-btn')
                    </a>

                    <!-- Save Button -->
                    <button class="primary-button">
                        @lang('admin::app.catalog.products.edit.save-btn')
                    </button>
                </div>
            </div>
        </div>

        @php
            $channels = core()->getAllChannels();

            $currentChannel = core()->getRequestedChannel() ?? core()->getDefaultChannel();

            $currentLocale = core()->getRequestedLocale();

            $currentLocale = $currentChannel->locales->contains($currentLocale) ? $currentLocale : $currentChannel->locales->first();
        @endphp

        <!-- Channel and Locale Switcher -->
        <div class="flex gap-4 justify-between items-center mt-7 max-md:flex-wrap">
            <div class="flex gap-x-1 items-center justify-between w-full">
                <div class="flex relative">
                    <!-- Channel Switcher -->
                    <x-admin::dropdown>
                        <!-- Dropdown Toggler -->
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="
                                flex gap-x-1 items-center px-3 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-violet-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
                            >
                                <span class="icon-channel   text-2xl"></span>

                                {{ ! empty($currentChannel->name) ? $currentChannel->name : '[' . $currentChannel->code . ']' }}

                                <input type="hidden" name="channel" value="{{ $currentChannel->code }}" />

                                <span class="icon-chevron-down   text-2xl"></span>
                            </button>
                        </x-slot>

                        <!-- Dropdown Content -->
                        <x-slot:content class="!p-0">
                            @foreach ($channels as $channel)
                                <a
                                    href="?{{ Arr::query(['channel' => $channel->code, 'locale' => $currentLocale?->code]) }}"
                                    class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 dark:text-white"
                                >
                                    {{ ! empty($channel->name) ? $channel->name : '[' . $channel->code . ']' }}
                                </a>
                            @endforeach
                        </x-slot>
                    </x-admin::dropdown>

                    <!-- Locale Switcher -->
                    <x-admin::dropdown>
                        <!-- Dropdown Toggler -->
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="flex gap-x-1 items-center px-3 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-violet-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50 "
                            >
                                <span class="icon-language text-2xl"></span>

                                {{ $currentLocale?->name }}

                                <input type="hidden" name="locale" value="{{ $currentLocale?->code }}" />

                                <span class="icon-chevron-down text-2xl"></span>
                            </button>
                        </x-slot>

                        <!-- Dropdown Content -->
                        <x-slot:content class="!p-0">
                            @foreach ($currentChannel->locales->sortBy('name') as $locale)
                                <a
                                    href="?{{ Arr::query(['channel' => $currentChannel->code, 'locale' => $locale->code]) }}"
                                    class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale?->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                                >
                                    {{ $locale->name }}
                                </a>
                            @endforeach
                        </x-slot>
                    </x-admin::dropdown>
                </div>

                <div>
                    <v-custom-dropdown></v-custom-dropdown>
                </div>
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.product.edit.actions.after', ['product' => $product]) !!}

        <!-- body content -->
        {!! view_render_event('unopim.admin.catalog.product.edit.form.before', ['product' => $product]) !!}

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="left-column flex flex-col gap-2 flex-1 max-xl:flex-auto">
                @foreach ($product->attribute_family->familyGroups()->orderBy('position')->get() as $group)
                    {!! view_render_event('unopim.admin.catalog.product.edit.form.column_before', ['product' => $product]) !!}

                    <div class="flex flex-col gap-2">
                        @php
                            $customAttributes = $product->getEditableAttributes($group);
                            $groupLabel = $group->name;
                            $groupLabel = empty($groupLabel) ? "[{$group->code}]" : $groupLabel;
                        @endphp

                        @if (count($customAttributes))
                            {!! view_render_event('unopim.admin.catalog.product.edit.form.' . $group->code . '.before', ['product' => $product]) !!}

                            <div class="relative p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    {{ $groupLabel }}
                                </p>
                                <x-admin::products.dynamic-attribute-fields
                                    :fields="$customAttributes"
                                    :fieldValues="$product->values"
                                    :currentLocaleCode="$currentLocale->code"
                                    :currentChannelCode="$currentChannel->code"
                                    :channelCurrencies="$currentChannel->currencies"
                                    :variantFields="$product?->parent ? $product->parent->super_attributes->pluck('code')->toArray() : []"
                                    fieldsWrapper="values"
                                >
                                </x-admin::products.dynamic-attribute-fields>
                            </div>

                            {!! view_render_event('unopim.admin.catalog.product.edit.form.' . $group->code . '.after', ['product' => $product]) !!}
                        @endif

                        <!-- Product Type View Blade File -->
                    </div>

                    {!! view_render_event('unopim.admin.catalog.product.edit.form.column_after', ['product' => $product]) !!}
                @endforeach
            </div>
            <div class="right-column flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <!-- Categories View Blade File -->
                @include('admin::catalog.products.edit.categories', ['currentLocaleCode' => $currentLocale?->code, 'productCategories' => $product->values['categories'] ?? []])

                @includeIf('admin::catalog.products.edit.types.' . $product->type)

                <!-- Related, Cross Sells, Up Sells View Blade File -->
                @include('admin::catalog.products.edit.links', [
                    'upSellAssociations' => $product->values['associations']['up_sells'] ?? [],
                    'crossSellAssociations' => $product->values['associations']['cross_sells'] ?? [],
                    'relatedAssociations' => $product->values['associations']['related_products'] ?? [],
                ])

                <!-- Include Product Type Additional Blade Files If Any -->
                @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                    @includeIf($view)
                @endforeach
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.product.edit.form.after', ['product' => $product]) !!}
    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.product.edit.after', ['product' => $product]) !!}
    @pushOnce('scripts')
        <script type="text/x-template" id="v-translate-attribute-template">
            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                <div class="flex gap-x-2.5 items-center">
                    <p class="icon-language text-l text-gray-700 hover:bg-gray-100 rounded"
                        @click="resetForm();fetchAttribute();fetchSourceLocales();fetchTargetLocales();$refs.translationModal.toggle();"
                    >
                        @lang('admin::app.catalog.products.edit.translate.translate-btn')
                    </p>
                </div>
                <div>
                    <x-admin::form
                        v-slot="{ meta, errors, handleSubmit }"
                        as="div"
                        ref="translationForm"
                    >
                        <form @submit="handleSubmit($event, translate)" ref="translationForm">
                            <x-admin::modal ref="translationModal">
                                <x-slot:header>
                                    <p class="flex  items-center text-lg text-gray-800 dark:text-white font-bold">
                                        <span class="icon-magic text-2xl text-gray-800"></span>
                                        @lang('admin::app.catalog.products.edit.translate.title')
                                    </p>
                                </x-slot>
                                <x-slot:content>
                                    <x-admin::form.control-group v-if="attributesOptions" >
                                        <div class="flex flex-row gap-4 mb-5" v-show="! translatedValues && ! nothingToTranslate">
                                            <x-admin::form.control-group.label class="required w-full">
                                                @lang('admin::app.catalog.products.edit.translate.attributes')
                                            </x-admin::form.control-group.label>
                                            <div class="w-full ">
                                                <x-admin::form.control-group.control
                                                    type="multiselect"
                                                    name="attributes"
                                                    ref="attributesOptionsRef"
                                                    rules="required"
                                                    ::value="attributes"
                                                    ::options="attributesOptions"
                                                    class="w-full "
                                                >
                                                </x-admin::form.control-group.control>

                                                <x-admin::form.control-group.error control-name="attributes"></x-admin::form.control-group.error>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4" v-show="! translatedValues && ! nothingToTranslate">
                                            <x-admin::form.control-group  >
                                                <x-admin::form.control-group.label class="required">
                                                    @lang('admin::app.catalog.products.edit.translate.source-channel')
                                                </x-admin::form.control-group.label>
                                                @php
                                                    $channels = core()->getAllChannels();
                                                    $options = [];
                                                    foreach($channels as $channel)
                                                    {
                                                        $options[] = [
                                                            'id' => $channel->code,
                                                            'label' => $channel->name,
                                                            ];
                                                    }
                                                    $optionsInJson = json_encode($options);
                                                @endphp
                                                <x-admin::form.control-group.control
                                                    type="select"
                                                    name="channel"
                                                    rules="required"
                                                    ::value="sourceChannel"
                                                    :options="json_encode($options)"
                                                    @input="getSourceLocale"
                                                >
                                                </x-admin::form.control-group.control>

                                                <x-admin::form.control-group.error control-name="channel"></x-admin::form.control-group.error>
                                            </x-admin::form.control-group >

                                                <x-admin::form.control-group  >
                                                    <x-admin::form.control-group.label class="required">
                                                        @lang('admin::app.catalog.products.edit.translate.target-channel')
                                                    </x-admin::form.control-group.label>

                                                    <x-admin::form.control-group.control
                                                        type="select"
                                                        name="targetChannel"
                                                        rules="required"
                                                        ::value="targetChannel"
                                                        :options="json_encode($options)"
                                                        @input="getTargetLocale"
                                                    >
                                                    </x-admin::form.control-group.control>

                                                <x-admin::form.control-group.error control-name="targetChannel"></x-admin::form.control-group.error>
                                            </x-admin::form.control-group >

                                            <x-admin::form.control-group v-if="localeOption">
                                                <x-admin::form.control-group.label class="required">
                                                    @lang('admin::app.catalog.products.edit.translate.locale')
                                                </x-admin::form.control-group.label>

                                                <x-admin::form.control-group.control
                                                    type="select"
                                                    name="locale"
                                                    rules="required"
                                                    ref="localelRef"
                                                    ::value="sourceLocale"
                                                    ::options="localeOption"
                                                    @input="resetTargetLocales"
                                                >
                                                </x-admin::form.control-group.control>

                                                <x-admin::form.control-group.error control-name="locale"></x-admin::form.control-group.error>
                                            </x-admin::form.control-group >

                                            <x-admin::form.control-group v-if="targetLocOptions">
                                                <x-admin::form.control-group.label class="required">
                                                    @lang('admin::app.catalog.products.edit.translate.target-locales')
                                                </x-admin::form.control-group.label>
                                                <x-admin::form.control-group.control
                                                    type="multiselect"
                                                    id="section"
                                                    ref="targetLocOptionsRef"
                                                    name="targetLocale"
                                                    rules="required"
                                                    ::value="targetLocales"
                                                    ::options="targetLocOptions"
                                                    track-by="id"
                                                    label-by="label"
                                                >
                                                </x-admin::form.control-group.control>

                                                <x-admin::form.control-group.error control-name="targetLocale"></x-admin::form.control-group.error>
                                            </x-admin::form.control-group >
                                        </div>

                                        </x-admin::form.control-group >

                                        <x-admin::form.control-group class="mt-5" v-if="translatedValues">
                                        <div class="w-full">
                                            <div class="flex flex-row justify-around gap-5 mb-4">
                                                <p class="text-sm text-gray-800 dark:text-white font-bold">@lang('admin::app.catalog.products.edit.translate.source-content')</p>
                                                <p class="text-sm text-gray-800 dark:text-white font-bold">@lang('admin::app.catalog.products.edit.translate.translated-content')</p>
                                            </div>

                                            <div v-for="(data,index) in translatedValues" :key="index" class="mb-4 flex flex-row gap-5">
                                                <div class="w-full">
                                                    <div class="inline-flex justify-between w-full">
                                                        <x-admin::form.control-group.label class="text-left pr-2">
                                                            @{{data.fieldLabel}}
                                                        </x-admin::form.control-group.label>
                                                        <div class="self-end mb-2 text-xs flex gap-1 items-center">
                                                            <span class="icon-channel uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border text- border-gray-200  text-gray-600 dark:!text-gray-600">
                                                                @{{sourceChannel}}
                                                            </span>
                                                            <span class="icon-language uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200  text-gray-600 dark:!text-gray-600">
                                                                @{{sourceLocale}}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <x-admin::form.control-group.control
                                                        type="text"
                                                        class="h-[30px] w-full"
                                                        ::name="data.fieldName"
                                                        ::value="data.sourceData"
                                                        readOnly
                                                        disabled
                                                        v-if="data.type == 'text'"
                                                    />

                                                    <x-admin::form.control-group.control
                                                        type="textarea"
                                                        class="h-[75px] w-full"
                                                        ::name="data.fieldName"
                                                        ::value="data.sourceData"
                                                        readOnly
                                                        disabled
                                                        v-if="data.type == 'textarea'"
                                                    />

                                                </div>

                                                <div class="w-full">
                                                    <div class="inline-flex justify-between w-full">
                                                        <x-admin::form.control-group.label class="text-left">
                                                            @{{data.fieldLabel}}
                                                        </x-admin::form.control-group.label>
                                                        <div class="self-end mb-2 text-xs flex gap-1 items-center">
                                                            <span class="icon-channel uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border text- border-gray-200 text-gray-600 dark:!text-gray-600">
                                                                @{{targetChannel}}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="overflow-y-auto h-40 space-y-4 border rounded-lg p-2">
                                                        <div v-for="(item, idx) in data.translatedData"
                                                            :key="idx"
                                                            class="flex flex-col space-y-0"
                                                        >
                                                            <x-admin::form.control-group.label class="flex justify-end text-right mb-0">
                                                                <span class="icon-language uppercase box-shadow p-1 h-5 rounded-full bg-gray-100 border border-gray-200 text-gray-600 dark:!text-gray-600">
                                                                    @{{ item.locale }}
                                                                </span>
                                                            </x-admin::form.control-group.label>

                                                            <x-admin::form.control-group.control
                                                                type="text"
                                                                class="h-[30px] w-full border-gray-300 rounded mt-0"
                                                                ::name="`${data.fieldName}_${item.locale}`"
                                                                ::value="item.content"
                                                                v-model="item.content"
                                                                v-if="data.type == 'text'"
                                                            />
                                                            <x-admin::form.control-group.control
                                                                type="textarea"
                                                                class="h-[75px] w-full border-gray-300 rounded mt-0"
                                                                ::name="`${data.fieldName}_${item.locale}`"
                                                                ::value="item.content"
                                                                v-model="item.content"
                                                                v-if="data.type == 'textarea'"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mt-5" v-if="nothingToTranslate">
                                    <x-admin::form.control-group.label class="text-left">
                                        @lang('admin::app.catalog.products.edit.translate.translated-content')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        class="h-[75px]"
                                        name="content"
                                        v-model="nothingToTranslate"
                                    />
                                </x-admin::form.control-group>

                                </x-slot>

                                <x-slot:footer>
                                <div class="flex gap-x-2.5 items-center">
                                    <template v-if="! translatedValues && ! nothingToTranslate">
                                        <button
                                            type="submit"
                                            class="secondary-button"
                                            ::disabled = "isLoading"
                                        >
                                            <!-- Spinner -->
                                            <template v-if="isLoading">
                                                <img
                                                    class="animate-spin h-5 w-5 text-violet-700"
                                                    src="{{ unopim_asset('images/spinner.svg') }}"
                                                />

                                                @lang('admin::app.catalog.products.edit.translate.translating')
                                            </template>

                                            <template v-else>
                                                <span class="icon-magic text-2xl text-violet-700"></span>
                                                @lang('admin::app.catalog.products.edit.translate.translate-btn')
                                            </template>
                                        </button>
                                    </template>

                                    <template v-else>
                                        <button
                                            v-if="translatedValues"
                                            type="button"
                                            class="primary-button"
                                            :disabled="!translatedValues"
                                            @click="apply"
                                        >
                                            @lang('admin::app.catalog.products.edit.translate.apply')
                                        </button>

                                        <button
                                            v-else-if="nothingToTranslate"
                                            type="button"
                                            class="secondary-button"
                                            @click="cancel"
                                        >
                                            @lang('admin::app.catalog.products.edit.translate.cancel')
                                        </button>
                                    </template>
                                </div>

                                </x-slot>
                            </x-admin::modal>
                        </form>
                    </x-admin::form>
                </div>
            </div>
        </script>
        <script type="module">
            app.component('v-translate-attribute', {
                template: '#v-translate-attribute-template',
                props: [
                    'channelValue',
                    'localeValue',
                    'model',
                    'channelTarget',
                    'targetLocales'
                ],
                data() {
                    return {
                        attributesOptions: null,
                        attributes: null,
                        targetLocOptions: null,
                        localeOption: null,
                        resourceId: "{{ request()->id }}",
                        sourceData: null,
                        translatedValues: null,
                        isLoading: false,
                        nothingToTranslate: '',
                        sourceLocale: this.localeValue,
                        sourceChannel: this.channelValue,
                        targetChannel: this.channelTarget,
                        targetLocales: this.targetLocales,
                        fieldType: this.fieldType,
                    };
                },
                methods: {
                    fetchAttribute() {
                        this.$axios.get("{{ route('admin.catalog.product.get_attribute') }}", {
                            params: {
                                productId: this.resourceId,
                                },
                            })
                            .then((response) => {
                                let options = response.data?.attributes;
                                this.attributesOptions = JSON.stringify(options);
                                this.attributes = options;
                                this.$nextTick(() => {
                                    if (this.$refs['attributesOptionsRef']) {
                                        this.$refs['attributesOptionsRef'].selectedValue = options;
                                    }
                                });

                            })
                            .catch((error) => {
                            console.error('Error fetching attributes:', error);
                                throw error;
                            });
                    },

                    fetchSourceLocales() {
                        this.getLocale(this.sourceChannel)
                            .then((options) => {
                                this.localeOption = JSON.stringify(options);
                            })
                            .catch((error) => {
                                console.error('Error fetching source locales:', error);
                            });
                    },

                    fetchTargetLocales() {
                        this.getLocale(this.targetChannel)
                            .then((options) => {
                                if (this.targetChannel === this.sourceChannel) {
                                    options = options.filter(option => option.id != this.sourceLocale);
                                }

                                this.targetLocOptions = JSON.stringify(options);
                            })
                            .catch((error) => {
                                console.error('Error fetching target locales:', error);
                            });
                    },

                    getSourceLocale(event) {
                        if (event) {
                            this.sourceChannel = JSON.parse(event).id;

                            this.getLocale(this.sourceChannel)
                                .then((options) => {
                                    if (this.$refs['localelRef']) {
                                        this.$refs['localelRef'].selectedValue = null;
                                    }

                                    this.localeOption = JSON.stringify(options);

                                    if (options.length == 1) {
                                        this.sourceLocale = options[0].id;

                                        if (this.$refs['localelRef']) {
                                            this.$refs['localelRef'].selectedValue = options[0];
                                        }
                                    }
                                })
                                .catch((error) => {
                                    console.error('Error fetching source locales:', error);
                                });
                        }
                    },    

                    getTargetLocale(event) {
                        if (event) {
                            this.targetChannel = JSON.parse(event).id;

                            this.getLocale(this.targetChannel)
                                .then((options) => {
                                    if (this.$refs['targetLocOptionsRef']) {
                                        this.$refs['targetLocOptionsRef'].selectedValue = null;
                                    }

                                    if (this.targetChannel === this.sourceChannel) {
                                        options = options.filter(option => option.id != this.sourceLocale);
                                    }

                                    this.targetLocOptions = JSON.stringify(options);
                                    this.targetLocales = options;

                                    if (this.$refs['targetLocOptionsRef']) {
                                        this.$refs['targetLocOptionsRef'].selectedValue = options;
                                    }
                                })
                                .catch((error) => {
                                    console.error('Error fetching source locales:', error);
                                });
                        }
                    },   

                    resetTargetLocales(event) {
                        if (event) {
                            this.sourceLocale = JSON.parse(event).id;
                            this.getLocale(this.targetChannel)
                                .then((options) => {
                                    if (this.$refs['targetLocOptionsRef']) {
                                        this.$refs['targetLocOptionsRef'].selectedValue = null;
                                    }
                                    if (this.targetChannel === this.sourceChannel) {
                                        options = options.filter(option => option.id != this.sourceLocale);
                                    }
                                    this.targetLocOptions = JSON.stringify(options);
                                    this.targetLocales = options;
                                    if (this.$refs['targetLocOptionsRef']) {
                                        this.$refs['targetLocOptionsRef'].selectedValue = options;
                                    }
                                })
                                .catch((error) => {
                                        console.error('Error fetching source locales:', error);
                                });

                        }
                    },

                    getLocale(channel) {
                        return this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                                params: {
                                    channel: channel,
                                },
                            })
                            .then((response) => {
                                return response.data?.locales || [];
                            })
                            .catch((error) => {
                                console.error('Error fetching locales:', error);
                                throw error;
                            });
                    },

                    translate(params, {
                        resetForm,
                        resetField,
                        setErrors
                    }) {
                        this.isLoading = true;
                        if (!this.$refs.translationForm) {
                            console.error("translationForm reference is missing.");
                            return;
                        }

                        const formData = new FormData(this.$refs.translationForm);
                        let locale = params['locale'];
                        formData.append('model', this.model);
                        formData.append('resource_id', this.resourceId);
                        formData.append('resource_type', 'product');
                        this.$axios.post("{{ route('admin.magic_ai.translate.all.attribute') }}", formData)
                            .then((response) => {
                                this.isLoading = false;
                                let translatedData = response.data;
                                if (translatedData.length != 0) {
                                    this.translatedValues = response.data;
                                } else {
                                    this.nothingToTranslate = 'Data not available for translate on the basis of source channel and locale';
                                }
                            })
                            .catch((error) => {
                                this.isLoading = false;
                                console.error("Error in translation request:", error);
                                if (setErrors) {
                                    setErrors(error.response?.data?.errors || {});
                                }
                            });
                    },

                    apply() {
                        const translatedData = this.translatedValues.map(item => ({
                            field: item.fieldName,
                            isTranslatable: item.isTranslatable,
                            source: item.sourceData,
                            translations: item.translatedData.map(translation => ({
                                locale: translation.locale,
                                content: translation.content
                            })),
                        }));

                        const formData = new FormData(this.$refs.translationForm);
                        formData.append('resource_id', this.resourceId);
                        formData.append('resource_type', 'product');
                        formData.append('translatedData', JSON.stringify(translatedData));
                        this.$axios.post("{{ route('admin.magic_ai.store.translated.all_attribute') }}", formData)
                            .then((response) => {
                                this.$refs.translationModal.close();
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            })
                            .catch((error) => {
                                console.error("Error in translation store request:", error);
                            });
                    },

                    cancel() {
                        this.$refs.translationModal.close();
                        this.resetForm();
                    },

                    resetForm() {
                        this.translatedValues = null;
                        this.localeOption = null;
                        this.nothingToTranslate = null;
                        this.targetLocOptions = null;
                    }

                },
            });
        </script>
        <script type="text/x-template" id="v-custom-dropdown-template">
            <div class="relative inline-block text-left">
                <button
                    type="button"
                    @click="toggleDropdown"
                    class="p-2 rounded-full hover:bg-gray-200 focus:outline-none"
                >
                    <span class="icon-configuration text-2xl"></span>

                </button>

                <div v-if="isOpen" class="absolute right-0  w-36 bg-white dark:bg-gray-700 shadow-lg rounded-lg z-100">
                    <p class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded cursor-pointer">
                        @php
                            $channelValue = core()->getConfigData('general.magic_ai.translation.source_channel');
                            $localeValue = core()->getConfigData('general.magic_ai.translation.source_locale');
                            $targetChannel = core()->getConfigData('general.magic_ai.translation.target_channel');
                            $targetlocales = core()->getConfigData('general.magic_ai.translation.target_locale');
                            $targetlocales = json_encode(explode(',', $targetlocales) ?? []);
                            $model = core()->getConfigData('general.magic_ai.translation.ai_model');
                        @endphp
                        <v-translate-attribute
                            :channel-value="{{ json_encode($channelValue) }}"
                            :locale-value='@json($localeValue)'
                            :channel-target="{{ json_encode($targetChannel) }}"
                            :target-locales="{{$targetlocales}}"
                            :model="'{{$model}}'"
                        >
                        </v-translate-attribute>
                    </p>
                </div>
            </div>
        </script>
        <script type="module">
            app.component('v-custom-dropdown', {
                template: '#v-custom-dropdown-template',
                data() {
                    return {
                        isOpen: false,
                    };
                },
                methods: {
                    toggleDropdown() {
                        this.isOpen = !this.isOpen;
                    },

                    closeDropdown(event) {
                        if (!this.$el.contains(event.target)) {
                            this.isOpen = false;
                        }
                    },

                    hideMenu() {
                        this.isOpen = false;
                    }
                },
                mounted() {
                    document.addEventListener('click', this.closeDropdown);
                },
                beforeUnmount() {
                    document.removeEventListener('click', this.closeDropdown);
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
