
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
                    @toggle="handleToggle"
                >
                    <x-slot:header>
                        <p class="flex items-center text-lg text-gray-800 dark:text-white font-bold">
                            @lang('admin::app.catalog.products.edit.translate.title')
                        </p>
                    </x-slot>
                    <x-slot:content class="flex gap-5 mt-3.5 max-xl:flex-wrap">
                        <section class="left-column flex flex-col gap-2 flex-1/5" :class="currentStep === 3 ? '' : 'w-full'">
                            <section class="grid gap-2 items-center justify-center modal-steps-section mb-4 dark:text-white">
                                <div class="flex justify-center items-center">
                                    <div class="w-3 h-3 bg-violet-700 rounded-full"></div>
                                    <hr class="w-[200px] dark:bg-cherry-600 h-1 border-0" :class="currentStep >= 2 ? 'bg-violet-700' : 'bg-violet-100'">
                                    <div class="w-3 h-3 bg-violet-400 rounded-full" :class="currentStep >= 2 ? 'bg-violet-700' : 'bg-violet-400'"></div>
                                </div>
    
                                <div class="flex justify-around items-center text-center dark:text-slate-50">
                                    <p class="text-sm" :class="currentStep === 1 ? 'text-violet-700' : ''">@lang('admin::app.catalog.products.edit.translate.step') 1 <br> @lang('admin::app.catalog.products.edit.translate.select-source')</p>
                                    <p class="text-sm" :class="currentStep === 2 ? 'text-violet-700' : ''">@lang('admin::app.catalog.products.edit.translate.step') 2 <br> @lang('admin::app.catalog.products.edit.translate.select-target')</p>
                                </div>
    
                                @lang('admin::app.catalog.products.edit.translate.first-step-title')
                            </section>
    
                            <section class="bg-violet-50 dark:bg-cherry-800 rounded-md mb-2 p-3" id="step-1">
                                <h3 class="dark:text-white mb-2 text-sm font-bold">
                                    @lang('admin::app.catalog.products.edit.translate.source-content')
                                </h3>
    
                                <!-- Source Channel -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.products.edit.translate.source-channel')
                                    </x-admin::form.control-group.label>
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
                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="channel"
                                        rules="required"
                                        ::value="sourceChannel"
                                        :options="$optionsInJson"
                                        @input="getSourceLocale"
                                        ::disabled="currentStep > 2"
                                    >
                                    </x-admin::form.control-group.control>
    
                                    <x-admin::form.control-group.error control-name="channel"></x-admin::form.control-group.error>
                                </x-admin::form.control-group >
    
                                <!-- Source Locale -->
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
                                    >
                                    </x-admin::form.control-group.control>
    
                                    <x-admin::form.control-group.error control-name="locale"></x-admin::form.control-group.error>
                                </x-admin::form.control-group >
    
                                <!-- Attributes -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required w-full">
                                        @lang('admin::app.catalog.products.edit.translate.attributes')
                                    </x-admin::form.control-group.label>
                                    <div class="w-full ">
                                        <x-admin::form.control-group.control
                                            type="multiselect"
                                            name="attributes"
                                            ref="attributesOptionsRef"
                                            rules="required"
                                            ::value="attributes ?? []"
                                            ::options="attributesOptions"
                                            class="w-full"
                                            ::disabled="currentStep > 2"
                                        />
    
                                        <x-admin::form.control-group.error control-name="attributes" />
                                    </div>
                                </x-admin::form.control-group>
                            </section>
    
                            <template v-if="currentStep > 1">
                                <h2 class="mt-6 mb-2 text-center">@lang('admin::app.catalog.products.edit.translate.second-step-title')</h2>
                                <section class="bg-violet-50 dark:bg-cherry-800 rounded-md mb-2 p-3" id="step-2">
                                    <h3 class="dark:text-white mb-2 text-sm font-bold">
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
                                </section>
                            </template>
                        </section>

                        <!-- Translated Content -->
                        <section class="right-column flex flex-col gap-2 w-full flex-2 max-xl:flex-auto" v-if="translatedValues">
                            <h3 class="text-gray-800 dark:text-white font-medium">@lang('admin::app.catalog.products.edit.translate.translated-content')</h3>
                            <table class="table-fixed border-4 border-violet-50 border-collapse w-full dark:border-cherry-700 dark:text-slate-50">
                                <thead>
                                    <th
                                        class="font-semibold relative border border-white dark:bg-cherry-700 dark:border-cherry-800 bg-violet-50 text-center"
                                        v-for="(data, index) in translatedValues.headers"
                                        v-text="data"
                                    ></th>
                                </thead>
                                 <tbody ref="tbody">
                                    <tr
                                        class="border-b dark:border-cherry-700"
                                        v-for="(data, locale) in translatedValues.translated"
                                    >
                                        <td
                                            class="bg-white dark:bg-cherry-800 border-r dark:border-cherry-700 p-2 text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            <span
                                                v-text="locale"
                                                class="w-full h-full text-sm text-gray-600 dark:text-gray-300 transition-all  focus:border-gray-400 dark:focus:border-gray-400 bg-transparent dark:border-gray-600"
                                            >
                                            </span>
                                        </td>
                                        <td
                                            class="bg-white dark:bg-cherry-800 border-r dark:border-cherry-700 p-2 text-sm text-gray-600 dark:text-gray-300"
                                            v-for="(translatedField) in data"
                                        >
                                            <input
                                                :value="translatedField.content"
                                                :type="translatedValues.fields[translatedField.field]?.type == 'textarea' ? 'textarea' : 'text'"
                                                :name="translatedField.field + '_' + locale"
                                                v-model="translatedField.content"
                                                class="w-full h-full text-sm text-gray-600 dark:text-gray-300 transition-all focus:border-gray-400 dark:focus:border-gray-400 bg-transparent dark:border-gray-600"
                                            />
                                        </td>
                                    </tr>
                                 </tbody>
                            </table>
                        </section>
                    </x-slot>

                    <x-slot:footer>
                        <div class="flex gap-x-2.5 items-center">
                            <template v-if="currentStep === 1">
                                <button
                                    type="button"
                                    class="secondary-button"
                                    @click="nextStep"
                                >
                                    @lang('admin::app.catalog.products.edit.translate.next')
                                </button>
                            </template>
                            <template v-else-if="currentStep === 2 && ! translatedValues">
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
                                    class="primary-button"
                                    ::disabled="isLoading"
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

                            <template v-else-if="translatedValues">
                                <button
                                    type="button"
                                    class="primary-button"
                                    @click="apply"
                                >
                                    @lang('admin::app.catalog.products.edit.translate.apply')
                                </button>
                            </template>
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

                                this.$emitter.emit('modal-size-change', 'full');
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
                nextStep(e) {
                    e.stopPropagation();

                    this.currentStep += 1;

                    this.$refs.translationModal.isOverflowing = true;
                },
                handleToggle(params) {
                    if (false === params?.isActive) {
                        this.currentStep = 1;

                        this.$emitter.emit('modal-size-change', 'medium');
                    }
                }
            },
        });
    </script>
@endPushOnce
