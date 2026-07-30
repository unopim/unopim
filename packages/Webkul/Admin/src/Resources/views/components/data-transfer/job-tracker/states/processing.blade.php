                <div
                    class="p-5"
                    v-else-if="importResource.state == 'processing' || importResource.state == 'processed'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.processing.before', $jobTrack) !!}
                    <div class="flex w-full items-start mb-5">
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
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-600 ring-4 ring-green-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-700 text-center leading-tight">
                                <template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template>
                                <template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template>
                            </span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 mt-0.5 animate-spin text-green-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="font-semibold text-green-700 dark:text-green-300" v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.imports.import.importing-info')</p>
                                    <p class="font-semibold text-green-700 dark:text-green-300" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.exporting-info')</p>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5" v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.imports.import.importing-info-sub')</p>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.exporting-info-sub')</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center flex-shrink-0 ml-4">
                                <button
                                    class="transparent-button text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900"
                                    @click="pauseImport"
                                    :disabled="isActionInProgress"
                                >
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                    @lang('admin::app.settings.data-transfer.tracker.pause')
                                </button>
                                <button
                                    class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900"
                                    @click="cancelImport"
                                    :disabled="isActionInProgress"
                                >
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                                <a
                                    class="transparent-button hover:dark:bg-cherry-800"
                                    href="{{ route('admin.settings.data_transfer.tracker.log.download', $jobTrack->id) }}"
                                    target="_blank"
                                >
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                <template v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.tracker.import-progress')</template>
                                <template v-else>@lang('admin::app.settings.data-transfer.tracker.export-progress')</template>
                            </span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-green-100 dark:bg-green-900/40 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full transition-all duration-500" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.total-batches')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ stats.batches.total }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.imports.import.completed-batches')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ stats.batches.completed }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-updated')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ Number(stats.summary?.updated || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-created')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(stats.summary?.created || 0).toLocaleString() }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l3.5 2"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.running-time')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.estimated-remaining')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedETA() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.records-deleted')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ Number(stats.summary?.deleted || 0).toLocaleString() }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            @lang('admin::app.settings.data-transfer.tracker.live') &middot; <template v-if="jobInstance.type == 'import'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template>
                        </span>
                    </div>
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.processing.after', $jobTrack) !!}
                </div>
