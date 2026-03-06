<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.tracker.index.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            <a
                href="{{ route('admin.settings.data_transfer.imports.index') }}"
                class="text-gray-600 hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white"
            >
                @lang('admin::app.settings.data-transfer.tracker.index.title')
            </a>

            - {{ ucfirst(trans($import->jobInstance->entity_type)) }} / {{ ucfirst(trans($import->jobInstance->code)) }}
        </p>

        <!-- Edit Button -->
        @if ($import->jobInstance->type !== 'system')
            <a
                href="{{ route(sprintf('admin.settings.data_transfer.%ss.edit', $import->jobInstance->type), $import->jobInstance->id) }}"
                class="primary-button"
            >
                @lang('admin::app.settings.data-transfer.tracker.import.edit-btn')
            </a>
        @endif
    </div>

    <!-- Import Vue Compontent -->
    <v-import />

    @pushOnce('scripts')
        <script type="text/x-template" id="v-import-template">
            <!-- Body Content -->
            <div class="grid gap-2.5 mt-3.5 p-5 max-xl:flex-wrap box-shadow">
               
                <div
                    class="grid gap-3 w-full p-4 bg-orange-50 border border-orange-200 rounded-sm"
                    v-if="importResource.state == 'pending'"
                >
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start">
                        <!-- Step 1: Queued (active) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-500 ring-2 ring-orange-300 ring-offset-1 animate-pulse">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 6v6l3.5 2"/>
                                </svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <!-- Step 2: Validating (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 000 4h6a2 2 0 000-4M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <!-- Step 3: Importing (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <!-- Step 4: Complete (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message below track -->
                    <p class="flex gap-2 items-center">
                        <i class="icon-information text-2xl text-orange-600 bg-orange-200 rounded-full dark:!text-orange-600"></i>
                        <span class="text-orange-700 font-medium">@lang('admin::app.settings.data-transfer.imports.import.pending-info')</span>
                        <span class="flex gap-1 ml-1 items-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce" style="animation-delay:0s"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce" style="animation-delay:.15s"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce" style="animation-delay:.3s"></span>
                        </span>
                    </p>
                </div>
                <!-- Validating -->
                <div
                    class="grid gap-3 w-full p-4 bg-violet-50 border border-violet-200 rounded-sm"
                    v-else-if="importResource.state == 'validating'"
                >
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start">
                        <!-- Step 1: Queued (done) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-violet-400 mt-4 mx-1"></div>
                        <!-- Step 2: Validating (active) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-violet-600 ring-2 ring-violet-300 ring-offset-1 animate-pulse">
                                <svg class="w-3.5 h-3.5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-violet-700 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <!-- Step 3: Importing (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <!-- Step 4: Complete (waiting) -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Message below track -->
                    <p class="flex gap-2 items-center">
                        <i class="icon-information text-2xl text-violet-700 bg-violet-200 rounded-full"></i>
                        <span class="text-violet-700 font-medium">@lang('admin::app.settings.data-transfer.imports.import.validating-info')</span>
                        <svg class="animate-spin h-5 w-5 ml-1 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </p>
                </div>

                <!-- Validation Results -->
                <div
                    class="grid gap-3 w-full p-3 border rounded-sm"
                    :class="isValid ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'"
                    v-else-if="importResource.state == 'validated'"
                >
                    <!-- Step pipeline (valid) -->
                    <div class="flex w-full items-start" v-if="isValid">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-orange-300 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-500 ring-2 ring-orange-300 ring-offset-1 animate-pulse">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l3.5 2"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Import Stats -->
                    <div class="grid gap-2">
                        <p
                            class="flex gap-2 items-center mb-2"
                            v-if="isValid"
                        >
                            <i class="icon-done h-fit rounded-full bg-green-200 text-2xl text-green-600 dark:!text-green-600"></i>

                            @lang('admin::app.settings.data-transfer.imports.import.validation-success-info')

                            <!-- Pulsing dots indicating auto-start -->
                            <span class="flex gap-1 ml-1 items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:0s"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:.15s"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:.3s"></span>
                            </span>
                        </p>

                        <p
                            class="flex gap-2 items-center"
                            v-else
                        >
                            <i class="icon-cancel h-fit rounded-full bg-red-200 text-2xl text-red-600"></i>

                            @lang('admin::app.settings.data-transfer.imports.import.validation-failed-info')
                        </p>
                        
                        <p class="flex gap-2 items-center">
                            <i
                                class="icon-information text-2xl rounded-full"
                                :class="isValid ? 'bg-green-200 text-green-600 dark:!text-green-600' : 'bg-red-200 text-red-600'"
                            ></i>

                            <span class="text-gray-800 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.import.total-rows-processed')
                            </span>

                            @{{ importResource.processed_rows_count }}
                        </p>

                        <p class="flex gap-2 items-center">
                            <i
                                class="icon-information text-2xl rounded-full"
                                :class="isValid ? 'bg-green-200 text-green-600 dark:!text-green-600' : 'bg-red-200 text-red-600'"
                            ></i>

                            <span class="text-gray-800 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.import.total-invalid-rows')
                            </span>

                            @{{ importResource.invalid_rows_count }}
                        </p>

                        <p class="flex gap-2 items-center">
                            <i
                                class="icon-information text-2xl rounded-full"
                                :class="isValid ? 'bg-green-200 text-green-600 dark:!text-green-600' : 'bg-red-200 text-red-600'"
                            ></i>

                            <span class="text-gray-800 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.import.total-errors')
                            </span>

                            @{{ importResource.errors_count }}
                        </p>

                        <div
                            class="flex gap-2 items-center place-items-start"
                            v-if="importResource.errors?.length"
                        >
                            <i class="icon-information bg-red-200 text-red-600 text-2xl rounded-full"></i>

                            <div class="grid gap-2">
                                <p
                                    class="break-all"
                                    v-for="error in importResource.errors"
                                >
                                    @{{ error }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a
                            class="secondary-button place-self-start"
                            href="{{ route('admin.settings.data_transfer.imports.download_error_report', $import->id) }}"
                            target="_blank"
                            v-if="importResource.errors_count && importResource.error_file_path"
                        >
                            @lang('admin::app.settings.data-transfer.imports.import.download-error-report')
                        </a>
                    </div>
                </div>

                <!-- Job Failed -->
                <div
                    class="flex place-content-between w-full p-3 border rounded-sm"
                    :class="isValid ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'"
                    v-else-if="importResource.state == 'failed'"
                >
                    <!-- Import Stats -->
                    <div class="grid gap-2">
                        <p
                            class="flex gap-2 items-center"
                        >
                            <i class="icon-cancel h-fit rounded-full bg-red-200 text-2xl text-red-600"></i>

                            @lang('admin::app.settings.data-transfer.tracker.failed-info')
                        </p>

                        <div
                            class="flex gap-2 items-center place-items-start"
                            v-if="importResource.errors?.length"
                        >
                            <i class="icon-information bg-red-200 text-red-600 text-2xl rounded-full"></i>

                            <div class="grid gap-2">
                                <p
                                    class="break-all"
                                    v-for="error in importResource.errors"
                                >
                                    @{{ error }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import In Process -->
                <div
                    class="grid gap-2 w-full p-3 bg-green-50 border border-green-200 rounded-sm dark:bg-cherry-700 dark:border-cherry-700 dark:text-gray-300"
                    v-else-if="importResource.state == 'processing' || importResource.state == 'processed'"
                >
                    <!-- Step pipeline -->
                    <div class="flex w-full items-start mb-1">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-violet-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-violet-600 ring-2 ring-violet-300 ring-offset-1 animate-pulse">
                                <svg class="w-3.5 h-3.5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-violet-700 text-center leading-tight">
                                <template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template>
                                <template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template>
                            </span>
                        </div>
                        <div class="flex-1 border-t-2 border-gray-300 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 border-2 border-gray-300">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <div class="flex place-content-between">
                        <p 
                            class="flex gap-2 items-center"
                            v-if="jobInstance.type == 'import'"
                        >
                            <i class="icon-information rounded-full bg-green-200 text-2xl text-green-600 dark:!text-green-600"></i>
    
                            @lang('admin::app.settings.data-transfer.imports.import.importing-info')
                        </p>
    
                        <p 
                            class="flex gap-2 items-center"
                            v-if="jobInstance.type == 'export'"
                        >
                            <i class="icon-information rounded-full bg-green-200 text-2xl text-green-600 dark:!text-green-600"></i>
    
                            @lang('admin::app.settings.data-transfer.imports.import.exporting-info')
                        </p>

                        <a
                            class="transparent-button hover:dark:bg-cherry-800 place-self-start"
                            href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                            target="_blank"
                        >
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>

                    <div class="w-full bg-green-200 rounded-sm h-5 dark:bg-green-500">
                        <div
                            class="bg-green-600 h-5 rounded-sm"
                            :style="{ 'width': stats.progress + '%' }"
                        ></div>
                    </div>

                    <div class="flex justify-between items-start">
                        <p class="flex gap-2 items-center">
                            <span class="text-gray-800 dark:text-gray-300 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.import.progress')
                            </span>

                            @{{ stats.progress }}%
                        </p>

                        <div class="flex flex-col items-end gap-1" v-if="importResource.started_at">
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-400 font-medium">
                                    @lang('admin::app.settings.data-transfer.tracker.running-time')
                                </span>

                                @{{ formattedElapsed() }}
                            </p>

                            <p class="flex gap-2 items-center text-sm" v-if="stats.progress > 0 && stats.progress < 100">
                                <span class="text-gray-700 dark:text-gray-400 font-medium">
                                    @lang('admin::app.settings.data-transfer.tracker.estimated-remaining')
                                </span>

                                @{{ formattedETA() }}
                            </p>
                        </div>
                    </div>

                    <p class="flex gap-2 items-center">
                        <span class="text-gray-800 dark:text-gray-300 font-medium">
                            @lang('admin::app.settings.data-transfer.imports.import.total-batches')
                        </span>

                        @{{ stats.batches.total }}
                    </p>

                    <p class="flex gap-2 items-center">
                        <span class="text-gray-800 dark:text-gray-300 font-medium">
                            @lang('admin::app.settings.data-transfer.imports.import.completed-batches')
                        </span>

                        @{{ stats.batches.completed }}
                    </p>

                    <p 
                        class="flex gap-2 dark:text-gray-300 items-center"
                        v-for="(value, key) in stats.summary" :key="key"
                    >
                        <span class="text-gray-800 dark:text-gray-300 font-medium">
                            @{{key}}
                        </span>

                        @{{value}}
                    </p>
                </div>

                <!-- Linking In Process -->
                <div
                    class="grid gap-2 w-full p-3 bg-green-50 border border-green-200 rounded-sm"
                    v-else-if="importResource.state == 'linking'"
                >
                    <!-- Step pipeline: import done, completing (linking) -->
                    <div class="flex w-full items-start mb-1">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-orange-300 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-500 ring-2 ring-orange-300 ring-offset-1 animate-pulse">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <p class="flex gap-2 items-center">
                        <i class="icon-information rounded-full bg-green-200 text-2xl text-green-600"></i>

                        @lang('admin::app.settings.data-transfer.imports.import.linking-info')
                    </p>

                    <div class="w-full bg-green-200 rounded-sm h-5 dark:bg-green-700">
                        <div
                            class="bg-green-600 h-5 rounded-sm"
                            :style="{ 'width': stats.progress + '%' }"
                        ></div>
                    </div>

                    <div class="flex justify-between items-start">
                        <p class="flex gap-2 items-center">
                            <span class="text-gray-800 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.import.progress')
                            </span>

                            @{{ stats.progress }}%
                        </p>

                        <div class="flex flex-col items-end gap-1" v-if="importResource.started_at">
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 font-medium">
                                    @lang('admin::app.settings.data-transfer.tracker.running-time')
                                </span>

                                @{{ formattedElapsed() }}
                            </p>

                            <p class="flex gap-2 items-center text-sm" v-if="stats.progress > 0 && stats.progress < 100">
                                <span class="text-gray-700 font-medium">
                                    @lang('admin::app.settings.data-transfer.tracker.estimated-remaining')
                                </span>

                                @{{ formattedETA() }}
                            </p>
                        </div>
                    </div>

                    <p class="flex gap-2 items-center">
                        <span class="text-gray-800 font-medium">
                            @lang('admin::app.settings.data-transfer.imports.import.total-batches')
                        </span>

                        @{{ stats.batches.total }}
                    </p>

                    <p class="flex gap-2 items-center">
                        <span class="text-gray-800 font-medium">
                            @lang('admin::app.settings.data-transfer.imports.import.completed-batches')
                        </span>

                        @{{ stats.batches.completed }}
                    </p>
                </div>

                <!-- Indexing In Process -->
                <div
                    class="grid gap-2 w-full p-3 bg-green-50 border border-green-200 rounded-sm"
                    v-else-if="importResource.state == 'indexing'"
                >
                    <!-- Step pipeline: import done, completing (indexing) -->
                    <div class="flex w-full items-start mb-1">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-orange-300 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-orange-500 ring-2 ring-orange-300 ring-offset-1 animate-pulse">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <p class="flex gap-2 items-center">
                        <i class="icon-information rounded-full bg-green-200 text-2xl text-green-600"></i>

                        @lang('admin::app.settings.data-transfer.imports.import.indexing-info')
                    </p>

                    <div class="w-full bg-green-200 rounded-sm h-5 dark:bg-green-700">
                        <div
                            class="bg-green-600 h-5 rounded-sm"
                            :style="{ 'width': stats.progress + '%' }"
                        ></div>
                    </div>

                    <div class="flex justify-between items-start">
                        <p class="flex gap-2 items-center">
                            <span class="text-gray-800 font-medium">
                                @lang('admin::app.settings.data-transfer.imports.import.progress')
                            </span>

                            @{{ stats.progress }}%
                        </p>

                        <div class="flex flex-col items-end gap-1" v-if="importResource.started_at">
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 font-medium">
                                    @lang('admin::app.settings.data-transfer.tracker.running-time')
                                </span>

                                @{{ formattedElapsed() }}
                            </p>

                            <p class="flex gap-2 items-center text-sm" v-if="stats.progress > 0 && stats.progress < 100">
                                <span class="text-gray-700 font-medium">
                                    @lang('admin::app.settings.data-transfer.tracker.estimated-remaining')
                                </span>

                                @{{ formattedETA() }}
                            </p>
                        </div>
                    </div>

                    <p class="flex gap-2 items-center">
                        <span class="text-gray-800 font-medium">
                            @lang('admin::app.settings.data-transfer.imports.import.total-batches')
                        </span>

                        @{{ stats.batches.total }}
                    </p>

                    <p class="flex gap-2 items-center">
                        <span class="text-gray-800 font-medium">
                            @lang('admin::app.settings.data-transfer.imports.import.completed-batches')
                        </span>

                        @{{ stats.batches.completed }}
                    </p>
                </div>

                <!-- Import Completed -->
                <div
                    class="grid gap-3 w-full p-3 bg-green-50 border border-green-200 dark:bg-cherry-700 dark:border-cherry-700 dark:text-gray-300 rounded-sm"
                    v-else-if="importResource.state == 'completed'"
                >
                    <!-- Step pipeline: all done -->
                    <div class="flex w-full items-start">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</span>
                        </div>
                        <div class="flex-1 border-t-2 border-green-400 mt-4 mx-1"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500 ring-2 ring-green-300 ring-offset-1">
                                <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-1.5 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <!-- Stats + Actions -->
                    <div class="flex place-content-between">
                    <!-- Stats -->
                    <div class="grid gap-2">
                        <div
                            class="flex gap-2 items-center mb-3"
                            v-if="isValid"
                        >
                            <i class="icon-done h-fit rounded-full bg-green-200 text-2xl text-green-600 dark:!text-green-600 shrink-0"></i>

                            <div class="flex flex-wrap items-center gap-x-1 gap-y-1">
                                <span class="text-base font-semibold text-gray-800 dark:text-gray-100">
                                    Job
                                    <span class="text-green-700 dark:text-green-400">
                                        @{{ jobInstance.entity_type ? (jobInstance.entity_type.charAt(0).toUpperCase() + jobInstance.entity_type.slice(1)) : '' }}
                                        /
                                        @{{ jobInstance.code }}
                                    </span>
                                    completed successfully
                                </span>

                                <span
                                    v-if="importResource.started_at"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-sm font-bold bg-green-600 text-white whitespace-nowrap"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5"/>
                                    </svg>
                                    @{{ totalDuration() }}
                                </span>
                            </div>
                        </div>

                        <p 
                            class="flex gap-2 items-center"
                            v-for="(value, key) in summary" :key="key"
                        >
                            <i class="icon-information text-2xl text-green-600 bg-green-200 dark:!text-green-600 rounded-full"></i>
                            
                            <span class="text-gray-800 dark:text-gray-300 font-medium">
                                @{{key}}
                            </span>

                            @{{ value}}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2">
                        <template v-if="jobInstance.type == 'export' && importResource.file_path">
                            <a
                                class="secondary-button place-self-start"
                                href="{{ route('admin.settings.data_transfer.tracker.archive.download', $import->id) }}"
                                target="_blank"
                                v-if="toBoolean(jobInstance.filters.with_media)"
                            >
                                @lang('admin::app.settings.data-transfer.exports.export.download-created-file')
                            </a>
                            <a
                                class="secondary-button place-self-start"
                                href="{{ route('admin.settings.data_transfer.tracker.download', $import->id) }}"
                                target="_blank"
                                v-else
                            >
                                @lang('admin::app.settings.data-transfer.exports.export.download-created-file')
                            </a>
                        </template>
                        <template v-if="jobInstance.type == 'import'">
                            <a
                                class="secondary-button place-self-start"
                                href="{{ route('admin.settings.data_transfer.imports.download_error_report', $import->id) }}"
                                target="_blank"
                                v-if="importResource.errors_count && importResource.error_file_path"
                            >
                                @lang('admin::app.settings.data-transfer.imports.import.download-error-report')
                            </a>
                        </template>
                        <a
                            class="transparent-button hover:dark:bg-cherry-800 place-self-start"
                            href="{{ route('admin.settings.data_transfer.tracker.log.download', $import->id) }}"
                            target="_blank"
                        >
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-import', {
                template: '#v-import-template',

                data() {
                    return {
                        importResource: @json($import),
                        jobInstance: @json($jobInstance),
                        isValid: "{{ $isValid }}",
                        summary: @json($summary),

                        stats: @json($stats),

                        elapsedSeconds: 0,
                        clockInterval: null,
                        workStartedAt: null,
                    };
                },

                mounted() {   
                    this.getStats();
                },

                methods: {
                    toBoolean(value) {
                        return value.toLowerCase() == 1;
                    },

                    formatDuration(seconds) {
                        if (seconds < 60) return seconds + 's';
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        if (m < 60) return m + 'm ' + (s > 0 ? s + 's' : '');
                        const h = Math.floor(m / 60);
                        return h + 'h ' + (m % 60) + 'm';
                    },

                    formattedElapsed() {
                        return this.formatDuration(this.elapsedSeconds);
                    },

                    formattedETA() {
                        const progress = parseFloat(this.stats.progress);
                        if (!progress || progress <= 0 || progress >= 100 || !this.workStartedAt) return '—';
                        const workElapsed = (Date.now() - this.workStartedAt) / 1000;
                        if (workElapsed < 2) return '—';
                        const remaining = (workElapsed / progress) * (100 - progress);
                        return this.formatDuration(Math.floor(remaining));
                    },

                    totalDuration() {
                        if (this.importResource.started_at && this.importResource.completed_at) {
                            const start = new Date(this.importResource.started_at).getTime();
                            const end = new Date(this.importResource.completed_at).getTime();
                            return this.formatDuration(Math.floor((end - start) / 1000));
                        }
                        return this.formatDuration(this.elapsedSeconds);
                    },

                    startClock() {
                        if (this.clockInterval || !this.importResource.started_at) return;
                        const startTime = new Date(this.importResource.started_at).getTime();
                        this.elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                        this.clockInterval = setInterval(() => {
                            this.elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                        }, 1000);
                    },

                    stopClock() {
                        if (this.clockInterval) {
                            clearInterval(this.clockInterval);
                            this.clockInterval = null;
                        }
                    },
                    
                    validate() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.validate', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;

                                this.isValid = response.data.is_valid;
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },

                    start() {
                        this.importResource.state = 'validation';

                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.start', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;

                                this.stats = response.data.stats;
                                
                                this.getStats();
                            })
                            .catch(error => {
                                this.importResource.state = 'validated';
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },

                    link() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.link', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;

                                this.stats = response.data.stats;

                                if (this.importResource.state == 'linking') {
                                    this.link();
                                } else if (this.importResource.state == 'indexing') {
                                    this.index();
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },

                    index() {
                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.index_data', $import->id) }}")
                            .then((response) => {
                                this.importResource = response.data.import;

                                this.stats = response.data.stats;

                                if (this.importResource.state == 'indexing') {
                                    this.index();
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },
                    

                    getStats() {
                        let state = 'processed';

                        if (this.importResource.state == 'linking')  {
                            state = 'linked';
                        } else if (this.importResource.state == 'indexing') {
                            state = 'indexed';
                        }

                        this.$axios.get("{{ route('admin.settings.data_transfer.imports.stats', $import->id) }}/" + state)
                            .then((response) => {
                                this.importResource = response.data.import;

                                this.stats = response.data.stats;

                                this.isValid = response.data.isValid;
                                this.summary = response.data.summary;
                                this.jobInstance = response.data.jobInstance;

                                const activeStates = ['validating', 'processing', 'processed', 'linking', 'indexing'];
                                if (activeStates.includes(this.importResource.state)) {
                                    this.startClock();
                                    if (parseFloat(this.stats.progress) > 0 && this.workStartedAt === null) {
                                        this.workStartedAt = Date.now();
                                    }
                                } else {
                                    this.stopClock();
                                }

                                const pollingStates = ['pending', 'validating', 'validated', 'processing', 'processed', 'linking', 'indexing'];
                                if (pollingStates.includes(this.importResource.state)) {
                                    setTimeout(() => {
                                        this.getStats();
                                    }, 1000);
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    }
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
