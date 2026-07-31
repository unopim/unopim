                <div
                    class="p-5"
                    v-else-if="importResource.state == 'validated'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.validated.before', $jobTrack) !!}
                    <div class="flex w-full items-start mb-5" v-if="isValid">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 mt-5 mx-2 bg-gradient-to-r from-green-500 to-orange-500"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-500 ring-4 ring-orange-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l3.5 2"/></svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-orange-600 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <div
                        class="rounded-lg p-4 mb-4"
                        :class="isValid ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'"
                    >
                        <p class="flex gap-2 items-center mb-2" v-if="isValid">
                            <i class="icon-done h-fit rounded-full bg-green-200 text-2xl text-green-600 dark:!text-green-600"></i>
                            @lang('admin::app.settings.data-transfer.imports.import.validation-success-info')
                            <span class="flex gap-1 ml-1 items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:0s"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:.15s"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-bounce" style="animation-delay:.3s"></span>
                            </span>
                        </p>
                        <p class="flex gap-2 items-center mb-2 text-red-800 dark:text-red-300 font-semibold" v-else>
                            <i class="icon-cancel h-fit rounded-full bg-red-200 dark:bg-red-800 text-2xl text-red-600 dark:text-red-200"></i>
                            @lang('admin::app.settings.data-transfer.imports.import.validation-failed-info')
                        </p>

                        <div class="grid gap-1.5 ml-8">
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">@lang('admin::app.settings.data-transfer.imports.import.total-rows-processed')</span>
                                @{{ importResource.processed_rows_count }}
                            </p>
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">@lang('admin::app.settings.data-transfer.imports.import.total-invalid-rows')</span>
                                @{{ importResource.invalid_rows_count }}
                            </p>
                            <p class="flex gap-2 items-center text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">@lang('admin::app.settings.data-transfer.imports.import.total-errors')</span>
                                @{{ importResource.errors_count }}
                            </p>
                            <div class="grid gap-1" v-if="importResource.errors?.length">
                                <p class="break-all text-sm text-red-600 dark:text-red-400" v-for="(error, index) in importResource.errors" :key="index">@{{ error }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2" v-if="importResource.errors_count && importResource.error_file_path">
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.imports.download_error_report', $jobTrack->id) }}"
                            target="_blank"
                        >
                            @lang('admin::app.settings.data-transfer.imports.import.download-error-report')
                        </a>
                    </div>
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.validated.after', $jobTrack) !!}
                </div>
