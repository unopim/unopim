@props([
    'globaltranslationEnabled' => core()->getConfigData('general.magic_ai.translation.enabled'),
    'channelValue'             => core()->getConfigData('general.magic_ai.translation.source_channel'),
    'localeValue'              => core()->getConfigData('general.magic_ai.translation.source_locale'),
    'targetChannel'            => core()->getConfigData('general.magic_ai.translation.target_channel'),
    'targetlocales'            => json_encode(explode(',', core()->getConfigData('general.magic_ai.translation.target_locale')) ?? []),
    'model'                    => core()->getConfigData('general.magic_ai.translation.ai_model'),
])

<v-translate-form
    :channel-value="{{ json_encode($channelValue) }}"
    :locale-value='@json($localeValue)'
    :channel-target="{{ json_encode($targetChannel) }}"
    :target-locales="{{$targetlocales}}"
    :id="'{{$field->code}}'"
    :value="'{{ json_encode(e($value)) }}'"
    :field="'{{$fieldLabel}}'"
    :field-type="'{{$fieldType}}'"
    :model="'{{$model}}'"
    :current-local-code="'{{ $currentLocaleCode }}'"
    :current-channel="'{{ $currentChannelCode }}'"
>
    <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
        <div class="flex gap-x-2.5 items-center">
            <button
                type="button"
                class="secondary-button bg-violet-50 text-violet-700 focus:ring-indigo-200 border border-indigo-200 rounded-lg px-2 h-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="none">
                    <g clip-path="url(#clip0_3148_2242)">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#6d28d9" />
                        <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#6d28d9" />
                        <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#6d28d9" />
                        <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#6d28d9" />
                    </g>
                    <defs>
                        <clipPath id="clip0_3148_2242">
                            <rect width="24" height="24" fill="white" />
                        </clipPath>
                    </defs>
                </svg>
                @lang('admin::app.catalog.products.edit.translate.translate-btn')
            </button>
        </div>
    </div>
</v-translate-form>
@pushOnce('scripts')
    <script type="text/x-template" id="v-translate-form-template">
        <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
            <div class="flex gap-x-2.5 items-center">
                <!-- translate Button -->
                <button
                    type="button"
                    class="secondary-button bg-violet-50 text-violet-700 focus:ring-indigo-200 border border-indigo-200 rounded-lg px-2 h-5"
                    @click="resetForm();fetchSourceLocales();fetchTargetLocales();$refs.translationModal.toggle();"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="none">
                        <g clip-path="url(#clip0_3148_2242)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#6d28d9"/>
                            <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#6d28d9"/>
                            <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#6d28d9"/>
                            <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#6d28d9"/>
                        </g>
                        <defs>
                            <clipPath id="clip0_3148_2242">
                                <rect width="24" height="24" fill="white"/>
                            </clipPath>
                        </defs>
                    </svg>
                    @lang('admin::app.catalog.products.edit.translate.translate-btn')
                </button>
            </div>
        </div>
        <div>
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="translationForm"
            >
                <form @submit="handleSubmit($event, translate)" ref="translationForm">
                    <x-admin::modal ref="translationModal" @toggle="handleToggle">
                        <x-slot:header>
                            <p class="flex items-center text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.products.edit.translate.title')
                            </p>
                        </x-slot>
                        <x-slot:content class="flex gap-5 mt-3.5 max-xl:flex-wrap text-base dark:text-whi">
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
                                </section>
        
                                <template v-if="currentStep > 1">
                                    <h2 class="mt-6 mb-2 text-center dark:text-white">@lang('admin::app.catalog.products.edit.translate.second-step-title')</h2>
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
                            <section class="right-column flex flex-col gap-2 w-full flex-2 max-xl:flex-auto" v-if="translatedData">
                                <h3 class="text-gray-800 dark:text-white font-medium">@lang('admin::app.catalog.products.edit.translate.translated-content')</h3>
                                <table class="table-fixed border-4 border-violet-50 border-collapse w-full dark:border-cherry-700 dark:text-slate-50">
                                    <thead>
                                        
                                        <th
                                            class="font-semibold relative border border-white dark:bg-cherry-700 dark:border-cherry-800 bg-violet-50 text-center"
                                            v-text="'Locale'"
                                        ></th>

                                        <th
                                            class="font-semibold relative border border-white dark:bg-cherry-700 dark:border-cherry-800 bg-violet-50 text-center"
                                            v-text="field"
                                        ></th>
                                    </thead>
                                    <tbody ref="tbody">
                                        <tr
                                            class="border-b dark:border-cherry-700"
                                            v-for="(data) in translatedData"
                                        >
                                            <td
                                                class="bg-white dark:bg-cherry-800 border-r dark:border-cherry-700 p-2 text-sm text-gray-600 dark:text-gray-300"
                                            >
                                                <span
                                                    v-text="data.locale"
                                                    class="w-full h-full text-sm text-gray-600 dark:text-gray-300 transition-all  focus:border-gray-400 dark:focus:border-gray-400 bg-transparent dark:border-gray-600"
                                                >
                                                </span>
                                            </td>
                                            <td
                                                class="bg-white dark:bg-cherry-800 border-r dark:border-cherry-700 p-2 text-sm text-gray-600 dark:text-gray-300"
                                            >
                                                <input
                                                    :value="data.content"
                                                    :type="fieldType == 'textarea' ? 'textarea' : 'text'"
                                                    :name="field + '_' + locale"
                                                    v-model="data.content"
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
                                <template v-else-if="currentStep === 2 && ! translatedData">
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

                                <template v-else-if="translatedData">
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
        </div>
    </script>

    <script type="module">
        app.component('v-translate-form', {
            template: '#v-translate-form-template',
            props: [
                'currentChannel',
                'currentLocalCode',
                'field',
                'fieldType',
                'selector',
                'value',
                'id',
                'channelValue',
                'localeValue',
                'model',
                'channelTarget',
                'targetLocales'

            ],
            data() {
                return {
                    targetLocOptions: null,
                    localeOption: null,
                    resourceId: "{{ request()->id }}",
                    sourceData: null,
                    translatedData: null,
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
                    if (!this.$refs.translationForm) {
                        console.error("translationForm reference is missing.");
                        return;
                    }

                    const formData = new FormData(this.$refs.translationForm);
                    let locale = params['locale'];
                    formData.append('model', this.model);
                    formData.append('resource_id', this.resourceId);
                    formData.append('resource_type', 'product');
                    formData.append('field', this.id);

                    this.$axios.post("{{route('admin.magic_ai.check.is_translatable')}}", formData)
                        .then((response) => {
                            if (response.data.isTranslatable) {
                                this.sourceData = response.data.sourceData;
                                this.$axios.post("{{ route('admin.magic_ai.translate') }}", formData)
                                    .then((response) => {
                                        this.isLoading = false;

                                        this.translatedData = response.data.translatedData;

                                        this.currentStep += 1;

                                        this.$emitter.emit('modal-size-change', 'full');
                                    })
                                    .catch((error) => {
                                        console.error("Error in translation request:", error);
                                        if (setErrors) {
                                            setErrors(error.response?.data?.errors || {});
                                        }
                                    });
                            } else {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: '@lang("admin::app.catalog.products.edit.translate.empty-translation-data")'
                                })

                                this.isLoading = false;
                            }
                        })
                        .catch((error) => {
                            console.error("Error in translation check request:", error);
                        });
                },

                apply() {
                    const translatedData = this.translatedData.map(item => ({
                        locale: item.locale,
                        content: item.content,
                    }));

                    const formData = new FormData(this.$refs.translationForm);
                    formData.append('resource_id', this.resourceId);
                    formData.append('resource_type', 'product');
                    formData.append('field', this.id);
                    formData.append('translatedData', JSON.stringify(translatedData));
                    this.$axios.post("{{ route('admin.magic_ai.store.translated') }}", formData)
                        .then((response) => {
                            this.$refs.translationModal.close();
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        })
                        .catch((error) => {

                        });
                },

                cancel() {
                    this.$refs.translationModal.close();
                    this.resetForm();
                },

                resetForm() {
                    this.translatedData = null;
                    this.localeOption = null;
                    this.targetLocOptions = null;
                },
                nextStep(e) {
                    e.stopPropagation();

                    this.currentStep += 1;
                },
                handleToggle(params) {
                    if (false === params?.isActive) {
                        this.currentStep = 1;

                        this.$emitter.emit('modal-size-change', 'medium');
                    }
                }
            }
        });
    </script>
@endPushOnce
