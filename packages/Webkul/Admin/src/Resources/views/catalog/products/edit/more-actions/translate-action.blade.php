
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
            @click="resetForm();fetchAttribute();fetchSourceLocales();fetchTargetLocales();$refs.translationModal.toggle();"
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
                    <x-slot:header>
                        <p class="flex items-center text-lg text-gray-800 dark:text-white font-bold">
                            @lang('admin::app.catalog.products.edit.translate.title')
                        </p>
                    </x-slot>
                    <x-slot:content class="flex gap-5 max-xl:flex-wrap text-base dark:text-white !p-0">
                        <!-- Steps 1 & 2: Source/Target Selection (centered layout) -->
                        <template v-if="!translatedValues">
                            <div class="w-full max-w-lg mx-auto py-6 px-4">
                                <!-- Step Indicator -->
                                <div class="flex items-center justify-center mb-6">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center justify-center min-w-[28px] min-h-[28px] w-7 h-7 rounded-full text-xs font-bold text-white bg-violet-700 shrink-0">1</span>
                                        <span class="inline-block w-20 h-0.5 mx-1" :class="currentStep >= 2 ? 'bg-violet-700' : 'bg-gray-200 dark:bg-gray-600'"></span>
                                        <span class="inline-flex items-center justify-center min-w-[28px] min-h-[28px] w-7 h-7 rounded-full text-xs font-bold shrink-0" :class="currentStep >= 2 ? 'bg-violet-700 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">2</span>
                                    </div>
                                </div>
                                <div class="flex justify-center gap-10 mb-6 text-xs text-gray-500 dark:text-gray-400">
                                    <span :class="currentStep === 1 ? 'text-violet-700 dark:text-violet-400 font-semibold' : ''">@lang('admin::app.catalog.products.edit.translate.select-source')</span>
                                    <span :class="currentStep === 2 ? 'text-violet-700 dark:text-violet-400 font-semibold' : ''">@lang('admin::app.catalog.products.edit.translate.select-target')</span>
                                </div>

                                <!-- Source Content Card -->
                                <div class="bg-violet-50 dark:bg-cherry-800 rounded-lg p-4 mb-4">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">
                                        @lang('admin::app.catalog.products.edit.translate.source-content')
                                    </h3>

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
                                            ::disabled="currentStep > 2"
                                        />
                                        <x-admin::form.control-group.error control-name="channel" />
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group v-if="localeOption">
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.edit.translate.locale')
                                        </x-admin::form.control-group.label>
                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="locale"
                                            rules="required"
                                            ref="localeRef"
                                            ::value="sourceLocale"
                                            ::options="localeOption"
                                            @input="resetTargetLocales"
                                            ::disabled="currentStep > 2"
                                        />
                                        <x-admin::form.control-group.error control-name="locale" />
                                    </x-admin::form.control-group>

                                    <!-- Attributes -->
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
                                            ::options="attributesOptions"
                                            ::disabled="currentStep > 2"
                                        />
                                        <x-admin::form.control-group.error control-name="attributes" />
                                    </x-admin::form.control-group>
                                </div>

                                <!-- Target Content Card (Step 2) -->
                                <template v-if="currentStep > 1">
                                    <div class="bg-violet-50 dark:bg-cherry-800 rounded-lg p-4">
                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">
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
                                                ::disabled="currentStep > 2"
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
                                                ::disabled="currentStep > 2"
                                            />
                                            <x-admin::form.control-group.error control-name="targetLocale" />
                                        </x-admin::form.control-group>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Step 3: Translation Preview (full-width clean layout) -->
                        <template v-if="translatedValues">
                            <div class="w-full px-4 py-4">
                                <!-- Summary Banner -->
                                <div class="flex items-center gap-3 bg-violet-50 dark:bg-cherry-800 rounded-lg px-4 py-3 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none" class="shrink-0">
                                        <g clip-path="url(#clip0_bulk_preview)">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#6d28d9"/>
                                            <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#6d28d9"/>
                                            <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#6d28d9"/>
                                        </g>
                                        <defs><clipPath id="clip0_bulk_preview"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                            @lang('admin::app.catalog.products.edit.translate.translated-content')
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @{{ Object.keys(translatedValues.translated).length }} @lang('admin::app.catalog.products.edit.translate.locale')@{{ Object.keys(translatedValues.translated).length > 1 ? 's' : '' }}
                                            &middot; @{{ Object.keys(translatedValues.fields).length }} @lang('admin::app.catalog.products.edit.translate.attributes')
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
                                                <span class="inline-block w-2 h-2 rounded-full bg-violet-500"></span>
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
                                                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-transparent border border-gray-200 dark:border-cherry-700 rounded-md p-2 focus:outline-none focus:ring-1 focus:ring-violet-500 focus:border-violet-500 resize-y"
                                                ></textarea>
                                                <input
                                                    v-else
                                                    v-model="translatedField.content"
                                                    type="text"
                                                    :name="translatedField.field + '_' + locale"
                                                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-transparent border border-gray-200 dark:border-cherry-700 rounded-md p-2 focus:outline-none focus:ring-1 focus:ring-violet-500 focus:border-violet-500"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </x-slot>

                    <x-slot:footer>
                        <div class="flex items-center justify-between w-full">
                            <div v-if="false"></div>
                            <div v-else></div>

                            <div class="flex gap-x-2.5 items-center">
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

                                <!-- Step 2: Cancel + Translate -->
                                <template v-else-if="currentStep === 2 && !translatedValues">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="cancel"
                                        ::disabled="isLoading"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.cancel')
                                    </button>
                                    <button
                                        type="submit"
                                        class="primary-button flex items-center gap-1.5"
                                        ::disabled="isLoading"
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

                                <!-- Step 3: Back + Cancel + Apply -->
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
                                        class="secondary-button"
                                        @click="cancel"
                                    >
                                        @lang('admin::app.catalog.products.edit.translate.cancel')
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
                    if (! this.$refs.translationForm) {
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
                    this.targetLocOptions = null;
                },
                goBackToStep2() {
                    this.translatedValues = null;
                    this.currentStep = 2;
                },
                nextStep(e) {
                    e.stopPropagation();

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
