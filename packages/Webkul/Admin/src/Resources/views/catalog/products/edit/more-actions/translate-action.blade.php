
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

@pushOnce('scripts')
    <script type="text/x-template" id="v-translate-attribute-template">
        <li
            class="w-full hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer px-3 py-2"
            @click="resetForm();fetchAttribute();fetchSourceLocales();fetchTargetLocales();fetchTranslatePlatforms();$refs.translationModal.toggle();"
        >
            <span
                class="icon-language text-gray-700 w-full"
                title="@lang('admin::app.catalog.products.edit.translate.translate-btn')"
            >
                @lang('admin::app.catalog.products.edit.translate.translate-btn')
            </span>
        </li>

        <x-admin::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
            ref="translationForm"
        >
            <form @submit="handleSubmit($event, translate)" ref="translationForm">
                <x-admin::modal
                    ref="translationModal"
                    clip
                    @toggle="handleToggle"
                >
                    <x-slot:header class="!px-6 !py-4">
                        <p class="flex items-center text-lg font-bold text-gray-800 dark:text-white">
                            @lang('admin::app.catalog.products.edit.translate.title')
                        </p>
                    </x-slot>
                    <x-slot:content class="text-base dark:text-white !p-0">
                        <template v-if="! translatedValues">
                            <div class="w-full px-6 py-5">
                                <div class="mb-6 flex items-start justify-center">
                                    <div class="flex w-28 flex-col items-center gap-1.5">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">1</span>

                                        <span
                                            class="text-xs"
                                            :class="currentStep === 1 ? 'font-semibold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'"
                                        >
                                            @lang('admin::app.catalog.products.edit.translate.select-source')
                                        </span>
                                    </div>

                                    <span
                                        class="mt-3.5 h-0.5 w-16 shrink-0 rounded"
                                        :class="currentStep >= 2 ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-600'"
                                    ></span>

                                    <div class="flex w-28 flex-col items-center gap-1.5">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                            :class="currentStep >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-600 dark:text-gray-400'"
                                        >
                                            2
                                        </span>

                                        <span
                                            class="text-xs"
                                            :class="currentStep === 2 ? 'font-semibold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'"
                                        >
                                            @lang('admin::app.catalog.products.edit.translate.select-target')
                                        </span>
                                    </div>
                                </div>

                                @php
                                    $channels = core()->getAllChannels();
                                    $options = [];

                                    foreach ($channels as $channel) {
                                        $channelName = $channel->name;

                                        $options[] = [
                                            'id'    => $channel->code,
                                            'label' => empty($channelName) ? "[$channel->code]" : $channelName,
                                        ];
                                    }

                                    $optionsInJson = json_encode($options);
                                @endphp

                                <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-cherry-700 dark:bg-cherry-800">
                                    <div class="mb-3 flex items-center justify-between gap-2.5">
                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                                            @lang('admin::app.catalog.products.edit.translate.source-content')
                                        </h3>

                                        <button
                                            v-if="currentStep > 1"
                                            type="button"
                                            class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400"
                                            @click="goBackToStep1"
                                        >
                                            @lang('admin::app.catalog.products.edit.translate.change')
                                        </button>
                                    </div>

                                    <div v-show="currentStep === 1">
                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.translate.source-channel')
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="select"
                                                name="channel"
                                                rules="required"
                                                ::value="sourceChannel"
                                                :options="$optionsInJson"
                                                @input="getSourceLocale"
                                            />

                                            <x-admin::form.control-group.error control-name="channel" />
                                        </x-admin::form.control-group>

                                        <x-admin::form.control-group v-if="localeOption">
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.translate.source-locale')
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="select"
                                                name="locale"
                                                rules="required"
                                                ref="localeRef"
                                                ::value="sourceLocale"
                                                ::options="localeOption"
                                                @input="resetTargetLocales"
                                            />

                                            <x-admin::form.control-group.error control-name="locale" />
                                        </x-admin::form.control-group>

                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.translate.attributes')
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="multiselect"
                                                name="attributes"
                                                ref="attributesOptionsRef"
                                                rules="required"
                                                ::value="attributes ?? []"
                                                ::options="attributesOptions ?? '[]'"
                                                @input="setSelectedAttributes"
                                            />

                                            <x-admin::form.control-group.error control-name="attributes" />
                                        </x-admin::form.control-group>
                                    </div>

                                    <div
                                        v-show="currentStep > 1"
                                        class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        <span>@{{ sourceChannelLabel }}</span>
                                        <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span>@{{ sourceLocaleLabel }}</span>
                                    </div>

                                    <div
                                        v-if="selectedAttributes.length"
                                        class="mt-4 border-t border-gray-200 pt-3 dark:border-cherry-700"
                                    >
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            @lang('admin::app.catalog.products.edit.translate.attributes-to-translate')
                                            (@{{ selectedAttributes.length }})
                                        </p>

                                        <div class="max-h-40 space-y-2 overflow-y-auto pr-1">
                                            <div
                                                v-for="attribute in selectedAttributes"
                                                :key="attribute.id"
                                                class="flex gap-3 text-sm"
                                            >
                                                <span
                                                    class="w-32 shrink-0 truncate text-gray-500 dark:text-gray-400"
                                                    :title="attribute.label"
                                                >
                                                    @{{ attribute.label }}
                                                </span>

                                                <span
                                                    class="truncate"
                                                    :class="previewValue(attribute.id) ? 'text-gray-800 dark:text-gray-200' : 'italic text-gray-400 dark:text-gray-500'"
                                                    :title="previewValue(attribute.id)"
                                                >
                                                    @{{ previewValue(attribute.id) || noValueLabel }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <template v-if="currentStep > 1">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-cherry-700 dark:bg-cherry-800">
                                        <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">
                                            @lang('admin::app.catalog.products.edit.translate.target-content')
                                        </h3>

                                        <x-admin::form.control-group>
                                            <x-admin::form.control-group.label class="required">
                                                @lang('admin::app.catalog.products.edit.translate.target-channel')
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="select"
                                                name="targetChannel"
                                                rules="required"
                                                ::value="targetChannel"
                                                :options="$optionsInJson"
                                                @input="getTargetLocale"
                                            />

                                            <x-admin::form.control-group.error control-name="targetChannel" />
                                        </x-admin::form.control-group>

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
                                            />

                                            <x-admin::form.control-group.error control-name="targetLocale" />
                                        </x-admin::form.control-group>

                                        <p class="flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="icon-information text-base leading-none"></span>

                                            @lang('admin::app.catalog.products.edit.translate.overwrite-warning')
                                        </p>
                                    </div>

                                </template>
                            </div>
                        </template>

                        <!-- Step 3: Translation Preview (full-width clean layout) -->
                        <template v-if="translatedValues">
                            <div class="w-full px-6 py-5">
                                <!-- Summary Banner -->
                                <div class="flex items-center gap-3 bg-primary-50 dark:bg-cherry-800 rounded-lg px-4 py-3 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none" class="shrink-0 text-primary-600 dark:text-primary-400">
                                        <g clip-path="url(#clip0_bulk_preview)">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="currentColor"/>
                                            <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="currentColor"/>
                                            <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="currentColor"/>
                                        </g>
                                        <defs><clipPath id="clip0_bulk_preview"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                            @lang('admin::app.catalog.products.edit.translate.translated-content')
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @lang('admin::app.catalog.products.edit.translate.target-locales'): @{{ Object.keys(translatedValues.translated).length }}
                                            &middot; @lang('admin::app.catalog.products.edit.translate.attributes'): @{{ Object.keys(translatedValues.fields).length }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Translation Cards per Locale -->
                                <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                                    <div
                                        v-for="(data, locale) in translatedValues.translated"
                                        :key="locale"
                                        class="border border-gray-200 dark:border-cherry-700 rounded-lg overflow-hidden"
                                    >
                                        <!-- Locale Header -->
                                        <div class="flex items-center bg-gray-50 dark:bg-cherry-800 px-4 py-2.5 border-b border-gray-200 dark:border-cherry-700">
                                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                <span class="inline-block w-2 h-2 rounded-full bg-primary-500"></span>
                                                @{{ locale }}
                                            </span>
                                        </div>

                                        <!-- Attribute Fields -->
                                        <div class="bg-white dark:bg-cherry-900 divide-y divide-gray-100 dark:divide-cherry-700">
                                            <div
                                                v-for="(translatedField) in data"
                                                :key="translatedField.field"
                                                class="px-4 py-3"
                                            >
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                                                    @{{ translatedValues.fields[translatedField.field]?.label || translatedField.field }}
                                                </label>
                                                <textarea
                                                    v-if="translatedValues.fields[translatedField.field]?.type === 'textarea'"
                                                    v-model="translatedField.content"
                                                    :name="translatedField.field + '_' + locale"
                                                    rows="3"
                                                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-transparent border border-gray-200 dark:border-cherry-700 rounded-md p-2 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 resize-y"
                                                ></textarea>
                                                <input
                                                    v-else
                                                    v-model="translatedField.content"
                                                    type="text"
                                                    :name="translatedField.field + '_' + locale"
                                                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-transparent border border-gray-200 dark:border-cherry-700 rounded-md p-2 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </x-slot>

                    <x-slot:footer class="!px-6 !py-4">
                        <div class="flex w-full items-center justify-between">
                            <div
                                class="flex items-center gap-2"
                                v-if="currentStep === 2 && ! translatedValues && translateModels.length"
                            >
                                <x-admin::form.control-group class="multiselect-compact !mb-0 w-40">
                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="platform_id"
                                        ::value="translatePlatformId"
                                        ::options="platformOptions"
                                        :label="trans('admin::app.components.tinymce.ai-generation.platform')"
                                        @input="setTranslatePlatform"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="multiselect-compact !mb-0 w-40">
                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="model"
                                        ::value="translateModel"
                                        ::options="modelOptions"
                                        :label="trans('admin::app.components.tinymce.ai-generation.model')"
                                        @input="setTranslateModel"
                                    />
                                </x-admin::form.control-group>
                            </div>

                            <div v-else></div>

                            <div class="flex items-center gap-x-2.5">
                                <!-- Step 1: Next -->
                                <template v-if="currentStep === 1">
                                    <button
                                        type="button"
                                        class="primary-button"
                                        @click="nextStep"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.next')
                                        <span class="icon-arrow-right text-lg"></span>
                                    </button>
                                </template>

                                <!-- Step 2: Back + Translate -->
                                <template v-else-if="currentStep === 2 && ! translatedValues">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="goBackToStep1"
                                        :disabled="isLoading"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.back')
                                    </button>
                                    <button
                                        type="submit"
                                        class="primary-button flex items-center gap-1.5"
                                        :disabled="isLoading"
                                    >
                                        <template v-if="isLoading">
                                            <img
                                                class="animate-spin h-4 w-4"
                                                src="{{ unopim_asset('images/spinner.svg') }}"
                                            />
                                            @lang('admin::app.catalog.products.edit.translate.translating')
                                        </template>
                                        <template v-else>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="none">
                                                <g clip-path="url(#clip0_bulk_footer)">
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="currentColor"/>
                                                    <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="currentColor"/>
                                                    <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="currentColor"/>
                                                </g>
                                                <defs><clipPath id="clip0_bulk_footer"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                            </svg>
                                            @lang('admin::app.catalog.products.edit.translate.translate-btn')
                                        </template>
                                    </button>
                                </template>

                                <!-- Step 3: Back + Apply -->
                                <template v-else-if="translatedValues">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="goBackToStep2"
                                    >
                                        &larr; @lang('admin::app.catalog.products.edit.translate.back')
                                    </button>
                                    <button
                                        type="button"
                                        class="primary-button"
                                        @click="apply"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.apply')
                                    </button>
                                </template>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </form>
        </x-admin::form>
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
                    attributeValues: {},
                    selectedAttributes: [],
                    channelOptions: {!! $optionsInJson !!},
                    noValueLabel: @json(trans('admin::app.catalog.products.edit.translate.no-value')),
                    translatePlatforms: [],
                    translateModels: [],
                    translatePlatformId: null,
                    translateModel: null,
                    targetLocOptions: null,
                    localeOption: null,
                    resourceId: "{{ request()->id }}",
                    sourceData: null,
                    translatedValues: null,
                    isLoading: false,
                    sourceLocale: this.localeValue,
                    sourceChannel: this.channelValue,
                    targetChannel: this.channelTarget,
                    targetLocales: this.targetLocales,
                    fieldType: this.fieldType,
                    currentStep: 1,
                };
            },

            computed: {
                platformOptions() {
                    return JSON.stringify(this.translatePlatforms.map((platform) => ({
                        id:    String(platform.id),
                        label: platform.label,
                    })));
                },

                modelOptions() {
                    return JSON.stringify(this.translateModels.map((model) => ({
                        id:    model,
                        label: model,
                    })));
                },

                sourceChannelLabel() {
                    return this.channelOptions.find((channel) => channel.id === this.sourceChannel)?.label ?? this.sourceChannel;
                },

                sourceLocaleLabel() {
                    return this.parsedLocaleOptions.find((locale) => locale.id === this.sourceLocale)?.label ?? this.sourceLocale;
                },

                parsedLocaleOptions() {
                    try {
                        return this.localeOption ? JSON.parse(this.localeOption) : [];
                    } catch (error) {
                        return [];
                    }
                },
            },

            methods: {
                setTranslatePlatform(event) {
                    if (! event) {
                        return;
                    }

                    this.translatePlatformId = JSON.parse(event).id;

                    this.onTranslatePlatformChange();
                },

                setTranslateModel(event) {
                    if (event) {
                        this.translateModel = JSON.parse(event).id;
                    }
                },

                fetchAttribute() {
                    this.$axios.get("{{ route('admin.catalog.product.get_attribute') }}", {
                        params: {
                            productId: this.resourceId,
                            channel:   this.sourceChannel,
                            locale:    this.sourceLocale,
                        },
                    })
                        .then((response) => {
                            let options = response.data?.attributes ?? [];

                            this.attributesOptions = JSON.stringify(options);
                            this.attributes = options;
                            this.attributeValues = response.data?.values ?? {};
                            this.selectedAttributes = options;

                            this.$nextTick(() => {
                                if (this.$refs['attributesOptionsRef']) {
                                    this.$refs['attributesOptionsRef'].selectedValue = options;
                                }
                            });
                        })
                        .catch((error) => {
                            console.error('Error fetching attributes:', error);
                        });
                },

                onTranslatePlatformChange() {
                    const platform = this.translatePlatforms.find((item) => String(item.id) === String(this.translatePlatformId));

                    this.translateModels = platform?.models ?? [];
                    this.translateModel = this.translateModels[0] ?? null;
                },

                fetchTranslatePlatforms() {
                    if (this.translatePlatforms.length) {
                        return;
                    }

                    this.$axios.get("{{ route('admin.magic_ai.platforms') }}")
                        .then((response) => {
                            this.translatePlatforms = response.data?.platforms ?? [];

                            const defaultPlatform = this.translatePlatforms.find((platform) => platform.is_default)
                                ?? this.translatePlatforms[0];

                            if (defaultPlatform) {
                                this.translatePlatformId = String(defaultPlatform.id);
                                this.translateModels = defaultPlatform.models ?? [];
                                this.translateModel = this.model && this.translateModels.includes(this.model)
                                    ? this.model
                                    : (this.translateModels[0] ?? null);
                            }
                        })
                        .catch((error) => {
                            console.error('Error fetching platforms:', error);
                        });
                },

                setSelectedAttributes(event) {
                    try {
                        this.selectedAttributes = event ? JSON.parse(event) : [];
                    } catch (error) {
                        this.selectedAttributes = [];
                    }
                },

                previewValue(code) {
                    const value = this.attributeValues?.[code];

                    if (value === null || value === undefined || typeof value === 'object') {
                        return '';
                    }

                    return String(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
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
                                if (this.$refs['localeRef']) {
                                    this.$refs['localeRef'].selectedValue = null;
                                }

                                this.localeOption = JSON.stringify(options);

                                if (options.length == 1) {
                                    this.sourceLocale = options[0].id;

                                    if (this.$refs['localeRef']) {
                                        this.$refs['localeRef'].selectedValue = options[0];
                                    }
                                }

                                this.fetchAttribute();
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

                        this.fetchAttribute();

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
                    if (! this.$refs.translationForm) {
                        console.error("translationForm reference is missing.");
                        return;
                    }

                    const formData = new FormData(this.$refs.translationForm);

                    formData.set('model', this.translateModel ?? this.model);

                    if (this.translatePlatformId) {
                        formData.set('platform_id', this.translatePlatformId);
                    }

                    formData.append('resource_id', this.resourceId);
                    formData.append('resource_type', 'product');
                    this.$axios.post("{{ route('admin.magic_ai.translate.all.attribute') }}", formData)
                        .then((response) => {
                            this.isLoading = false;

                            let translatedData = response.data;

                            if (translatedData.length != 0) {
                                this.translatedValues = response.data;

                                this.currentStep += 1;
                            } else {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: '@lang("admin::app.catalog.products.edit.translate.empty-translation-data")'
                                })
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
                    if (! this.translatedValues.translated) {
                        return;
                    }

                    let translatedData = [];

                    Object.keys(this.translatedValues.fields).forEach(fieldName => {
                        let fieldData = this.translatedValues.fields[fieldName];

                        translatedData.push({
                            field: fieldName,
                            isTranslatable: fieldData.isTranslatable,
                            source: fieldData.sourceData,

                            translations: Object.keys(this.translatedValues.translated).map(locale => {
                                return {
                                    locale: locale,
                                    content: this.translatedValues.translated[locale][fieldName].content,
                                };
                            })
                        });
                    });

                    const formData = new FormData(this.$refs.translationForm);

                    formData.append('resource_id', this.resourceId);
                    formData.append('resource_type', 'product');
                    formData.append('translatedData', JSON.stringify(translatedData));
                    formData.append('targetChannel', this.targetChannel);

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

                resetForm() {
                    this.translatedValues = null;
                    this.localeOption = null;
                    this.targetLocOptions = null;
                    this.attributeValues = {};
                    this.selectedAttributes = [];
                    this.currentStep = 1;
                },

                goBackToStep1() {
                    this.currentStep = 1;
                },

                goBackToStep2() {
                    this.translatedValues = null;
                    this.currentStep = 2;
                },

                nextStep(e) {
                    e.stopPropagation();

                    if (! this.sourceChannel || ! this.sourceLocale || ! this.selectedAttributes.length) {
                        this.$emitter.emit('add-flash', {
                            type:    'warning',
                            message: @json(trans('admin::app.catalog.products.edit.translate.incomplete-source')),
                        });

                        return;
                    }

                    this.currentStep += 1;

                    this.$refs.translationModal.isOverflowing = true;
                },
                handleToggle(params) {
                    if (false === params?.isActive) {
                        this.currentStep = 1;
                    }
                }
            },
        });
    </script>
@endPushOnce
