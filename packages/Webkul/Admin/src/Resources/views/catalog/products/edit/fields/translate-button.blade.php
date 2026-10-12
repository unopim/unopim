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
                class="secondary-button bg-primary-50 text-primary-700 focus:ring-indigo-200 border border-indigo-200 rounded-lg px-2 h-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="none">
                    <g clip-path="url(#clip0_3148_2242)">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="currentColor" />
                        <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="currentColor" />
                        <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="currentColor" />
                        <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="currentColor" />
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
                    class="secondary-button bg-primary-50 text-primary-700 focus:ring-indigo-200 border border-indigo-200 rounded-lg px-2 h-5"
                    @click="resetForm();fetchSourceLocales();fetchTargetLocales();fetchTranslatePlatforms();fetchSourcePreview();$refs.translationModal.toggle();"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="none">
                        <g clip-path="url(#clip0_3148_2242)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="currentColor"/>
                            <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="currentColor"/>
                            <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="currentColor"/>
                            <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="currentColor"/>
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
                    <x-admin::modal ref="translationModal" clip @toggle="handleToggle">
                        <x-slot:header class="!px-6 !py-4">
                            <p class="flex items-center text-lg font-bold text-gray-800 dark:text-white">
                                @lang('admin::app.catalog.products.edit.translate.title')
                            </p>
                        </x-slot>
                        <x-slot:content class="text-base dark:text-white !p-0">
                            <!-- Steps 1 & 2: Source/Target Selection (centered layout) -->
                            <template v-if="!translatedData">
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

                                    <!-- Source Content Card -->
                                    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-cherry-700 dark:bg-cherry-800">
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

                                        <div
                                            v-else
                                            class="mb-4 grid gap-1.5"
                                        >
                                            <div class="h-4 w-24 animate-pulse rounded bg-gray-200 dark:bg-cherry-700"></div>

                                            <div class="h-10 w-full animate-pulse rounded-md bg-gray-200 dark:bg-cherry-700"></div>
                                        </div>

                                        <x-admin::form.control-group class="!mb-0">
                                            <x-admin::form.control-group.label>
                                                <span v-text="field"></span>
                                            </x-admin::form.control-group.label>

                                            <p
                                                class="max-h-32 overflow-y-auto whitespace-pre-line rounded-md border border-gray-200 bg-white px-3 py-2 text-sm dark:border-cherry-700 dark:bg-cherry-900"
                                                :class="sourcePreview ? 'text-gray-700 dark:text-gray-300' : 'italic text-gray-400 dark:text-gray-500'"
                                            >
                                                @{{ sourcePreview || noValueLabel }}
                                            </p>
                                        </x-admin::form.control-group>
                                    </div>

                                    <!-- Target Content Card (Step 2) -->
                                    <template v-if="currentStep > 1">
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-cherry-700 dark:bg-cherry-800">
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

                                            <div
                                                v-else
                                                class="mb-4 grid gap-1.5"
                                            >
                                                <div class="h-4 w-24 animate-pulse rounded bg-gray-200 dark:bg-cherry-700"></div>

                                                <div class="h-10 w-full animate-pulse rounded-md bg-gray-200 dark:bg-cherry-700"></div>
                                            </div>

                                            <p class="flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="icon-information text-base leading-none"></span>

                                                @lang('admin::app.catalog.products.edit.translate.overwrite-warning')
                                            </p>
                                        </div>

                                    </template>
                                </div>
                            </template>

                            <!-- Step 3: Translation Preview (full-width clean layout) -->
                            <template v-if="translatedData">
                                <div class="w-full px-6 py-5">
                                    <!-- Summary Banner -->
                                    <div class="flex items-center gap-3 bg-primary-50 dark:bg-cherry-800 rounded-lg px-4 py-3 mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none" class="shrink-0 text-primary-600 dark:text-primary-400">
                                            <g clip-path="url(#clip0_preview)">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="currentColor"/>
                                                <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="currentColor"/>
                                                <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="currentColor"/>
                                            </g>
                                            <defs><clipPath id="clip0_preview"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                                @lang('admin::app.catalog.products.edit.translate.translated-content')
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                @lang('admin::app.catalog.products.edit.translate.target-locales'): @{{ translatedData.length }}
                                                &middot; <span v-text="field"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="sourceData"
                                        class="mb-3 overflow-hidden rounded-lg border border-gray-200 dark:border-cherry-700"
                                    >
                                        <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-cherry-700 dark:bg-cherry-800">
                                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                <span class="inline-block h-2 w-2 rounded-full bg-gray-400"></span>
                                                @lang('admin::app.catalog.products.edit.translate.source-content')

                                                <span
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                    v-text="localeLabel(sourceLocale)"
                                                ></span>
                                            </span>
                                        </div>

                                        <p
                                            class="whitespace-pre-line px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300"
                                            v-text="sourceData"
                                        ></p>
                                    </div>

                                    <!-- Translation Cards -->
                                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                                        <div
                                            v-for="(data, index) in translatedData"
                                            :key="data.locale"
                                            class="border border-gray-200 dark:border-cherry-700 rounded-lg overflow-hidden"
                                        >
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-cherry-800 px-4 py-2 border-b border-gray-200 dark:border-cherry-700">
                                                <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    <span class="inline-block w-2 h-2 rounded-full bg-primary-500"></span>
                                                    @{{ localeLabel(data.locale) }}
                                                </span>
                                            </div>
                                            <div class="p-3 bg-white dark:bg-cherry-900">
                                                <textarea
                                                    v-if="fieldType === 'textarea'"
                                                    v-model="data.content"
                                                    :name="field + '_' + data.locale"
                                                    rows="3"
                                                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-transparent border border-gray-200 dark:border-cherry-700 rounded-md p-2 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 resize-y"
                                                ></textarea>
                                                <input
                                                    v-else
                                                    v-model="data.content"
                                                    type="text"
                                                    :name="field + '_' + data.locale"
                                                    class="w-full text-sm text-gray-700 dark:text-gray-300 bg-transparent border border-gray-200 dark:border-cherry-700 rounded-md p-2 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                                />
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
                                    v-if="currentStep === 2 && ! translatedData && translateModels.length"
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
                                    <template v-else-if="currentStep === 2 && ! translatedData">
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
                                                    <g clip-path="url(#clip0_footer)">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="currentColor"/>
                                                        <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="currentColor"/>
                                                        <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="currentColor"/>
                                                    </g>
                                                    <defs><clipPath id="clip0_footer"><rect width="24" height="24" fill="white"/></clipPath></defs>
                                                </svg>
                                                @lang('admin::app.catalog.products.edit.translate.translate-btn')
                                            </template>
                                        </button>
                                    </template>

                                    <!-- Step 3: Back + Apply -->
                                    <template v-else-if="translatedData">
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
                    translatePlatforms: [],
                    translateModels: [],
                    translatePlatformId: null,
                    translateModel: null,
                    sourcePreview: null,
                    noValueLabel: @json(trans('admin::app.catalog.products.edit.translate.no-value')),
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

                fetchSourcePreview() {
                    this.$axios.get("{{ route('admin.catalog.product.get_attribute') }}", {
                        params: {
                            productId: this.resourceId,
                            channel:   this.sourceChannel,
                            locale:    this.sourceLocale,
                        },
                    })
                        .then((response) => {
                            const value = (response.data?.values ?? {})[this.id];

                            this.sourcePreview = (value === null || value === undefined || typeof value === 'object')
                                ? null
                                : String(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                        })
                        .catch((error) => {
                            console.error('Error fetching source value:', error);
                        });
                },

                async fetchTranslatePlatforms() {
                    if (this.translatePlatforms.length) return;
                    try {
                        const response = await axios.get("{{ route('admin.magic_ai.platforms') }}");
                        this.translatePlatforms = response.data.platforms || [];
                        if (this.translatePlatforms.length) {
                            let def = this.translatePlatforms.find(p => p.is_default);
                            this.translatePlatformId = String(def ? def.id : this.translatePlatforms[0].id);
                            this.onTranslatePlatformChange();
                        }
                    } catch (e) {
                        console.error('Failed to fetch platforms:', e);
                    }
                },

                onTranslatePlatformChange() {
                    let platform = this.translatePlatforms.find(p => String(p.id) === String(this.translatePlatformId));
                    if (platform && platform.models) {
                        this.translateModels = platform.models;
                        this.translateModel = this.translateModels[0] || null;
                    } else {
                        this.translateModels = [];
                        this.translateModel = null;
                    }
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

                                this.fetchSourcePreview();
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

                        this.fetchSourcePreview();
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

                localeLabel(code) {
                    const pools = [
                        this.parsedOptions(this.localeOption),
                        this.parsedOptions(this.targetLocOptions),
                    ];

                    for (const pool of pools) {
                        const locale = pool.find((locale) => locale.id === code);

                        if (locale?.label) {
                            return locale.label;
                        }
                    }

                    return code;
                },

                parsedOptions(options) {
                    try {
                        return options ? JSON.parse(options) : [];
                    } catch (error) {
                        return [];
                    }
                },

                getLocale(channel) {
                    /**
                     * Every attribute field renders its own translate button, so the cache is shared
                     * on the window to keep one request per channel for the whole page.
                     */
                    window.translateLocaleCache = window.translateLocaleCache || {};

                    if (window.translateLocaleCache[channel]) {
                        return window.translateLocaleCache[channel].then((locales) => locales.map((locale) => ({ ...locale })));
                    }

                    const request = this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                            params: {
                                channel: channel,
                            },
                        })
                        .then((response) => {
                            return response.data?.locales || [];
                        })
                        .catch((error) => {
                            delete window.translateLocaleCache[channel];

                            console.error('Error fetching locales:', error);

                            throw error;
                        });

                    window.translateLocaleCache[channel] = request;

                    return request.then((locales) => locales.map((locale) => ({ ...locale })));
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
                    formData.set('model', this.translateModel || this.model);
                    if (this.translatePlatformId) {
                        formData.set('platform_id', this.translatePlatformId);
                    }
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
                    formData.append('targetChannel', this.targetChannel);
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

                resetForm() {
                    this.translatedData = null;
                    this.localeOption = null;
                    this.targetLocOptions = null;
                    this.sourcePreview = null;
                    this.currentStep = 1;
                },

                goBackToStep1() {
                    this.currentStep = 1;
                },

                goBackToStep2() {
                    this.translatedData = null;
                    this.currentStep = 2;
                },

                nextStep(e) {
                    e.stopPropagation();

                    if (! this.sourceChannel || ! this.sourceLocale) {
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
            }
        });
    </script>
@endPushOnce
