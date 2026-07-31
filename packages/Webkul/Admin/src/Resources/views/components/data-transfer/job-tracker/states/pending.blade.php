                <div
                    class="p-5"
                    v-if="importResource.state == 'pending'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.pending.before', $jobTrack) !!}
                    <div class="flex w-full items-start mb-5">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-orange-500 ring-4 ring-orange-200 animate-pulse">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 6v6l3.5 2"/>
                                </svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-orange-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')</span>
                        </div>
                        <div class="flex-1 h-0.5 mt-5 mx-2 bg-gradient-to-r from-green-500 to-orange-500"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 000 4h6a2 2 0 000-4M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-validate')</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gray-100 border-2 border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 text-gray-400 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <span class="flex gap-1 mt-1 items-center flex-shrink-0">
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-bounce" style="animation-delay:0s"></span>
                                <span class="w-2 h-2 rounded-full bg-orange-500 animate-bounce" style="animation-delay:.15s"></span>
                                <span class="w-2 h-2 rounded-full bg-orange-400 animate-bounce" style="animation-delay:.3s"></span>
                            </span>
                            <div>
                                <p class="font-semibold text-orange-700 dark:text-orange-300">@lang('admin::app.settings.data-transfer.imports.import.pending-info')</p>
                                <p class="text-sm text-orange-600/80 dark:text-orange-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.imports.import.pending-info-sub')</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l3.5 2"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.status')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@lang('admin::app.settings.data-transfer.tracker.waiting')</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.total-records')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ importResource.processed_rows_count ? Number(importResource.processed_rows_count).toLocaleString() : '—' }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.job-id')
                            </p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">#@{{ importResource.id }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            @lang('admin::app.settings.data-transfer.imports.import.pending-step-queue')
                        </span>
                        <a
                            class="transparent-button text-sm hover:dark:bg-cherry-800"
                            href="{{ route('admin.settings.data_transfer.tracker.log.download', $jobTrack->id) }}"
                            target="_blank"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.pending.after', $jobTrack) !!}
                </div>
