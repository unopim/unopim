                <div
                    class="p-5"
                    v-else-if="importResource.state == 'completed'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.completed.before', $jobTrack) !!}
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
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight"><template v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.imports.import.pending-step-export')</template><template v-else>@lang('admin::app.settings.data-transfer.imports.import.pending-step-import')</template></span>
                        </div>
                        <div class="flex-1 h-0.5 bg-green-400 mt-5 mx-2"></div>
                        <div class="flex flex-col items-center flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-500 ring-4 ring-green-200">
                                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs mt-2 font-semibold text-green-600 text-center leading-tight">@lang('admin::app.settings.data-transfer.imports.import.pending-step-complete')</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <div>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                                            @lang('admin::app.settings.data-transfer.tracker.job-label') <span class="text-green-700 dark:text-green-400">@{{ jobInstance.entity_type ? (jobInstance.entity_type.charAt(0).toUpperCase() + jobInstance.entity_type.slice(1)) : '' }} / @{{ jobInstance.code }}</span>
                                            @lang('admin::app.settings.data-transfer.tracker.completed-success')
                                        </span>
                                        <span
                                            v-if="importResource.started_at"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-600 text-white whitespace-nowrap"
                                        >
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5"/></svg>
                                            @{{ totalDuration() }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-green-600/80 dark:text-green-400/80 mt-0.5">@lang('admin::app.settings.data-transfer.tracker.completed-info-sub')</p>
                                </div>
                            </div>
                            <a
                                class="transparent-button hover:dark:bg-cherry-800 flex-shrink-0 ml-4"
                                href="{{ route('admin.settings.data_transfer.tracker.log.download', $jobTrack->id) }}"
                                target="_blank"
                            >
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                            </a>
                        </div>
                    </div>

                    {{-- Stats grid (use importResource.summary for completed — it's the final aggregated data from the Completed job) --}}
                    <div class="grid grid-cols-4 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-created')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(importResource.summary?.created || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-updated')</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">@{{ Number(importResource.summary?.updated || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.records-deleted')</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(importResource.summary?.deleted || 0).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.total-duration')</p>
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400 mt-1">@{{ totalDuration() }}</p>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-2" v-if="jobInstance.type == 'export' && importResource.file_path">
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.tracker.archive.download', $jobTrack->id) }}"
                            target="_blank"
                            v-if="toBoolean(jobInstance.filters.with_media)"
                        >
                            @lang('admin::app.settings.data-transfer.exports.export.download-created-file')
                        </a>
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.tracker.download', $jobTrack->id) }}"
                            target="_blank"
                            v-else
                        >
                            @lang('admin::app.settings.data-transfer.exports.export.download-created-file')
                        </a>
                    </div>
                    <div class="flex gap-2 mb-2" v-if="jobInstance.type == 'import' && importResource.errors_count && importResource.error_file_path">
                        <a
                            class="secondary-button"
                            href="{{ route('admin.settings.data_transfer.imports.download_error_report', $jobTrack->id) }}"
                            target="_blank"
                        >
                            @lang('admin::app.settings.data-transfer.imports.import.download-error-report')
                        </a>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.completed')
                        </span>
                        <a
                            class="primary-button"
                            href="{{ route('admin.settings.data_transfer.imports.import-view', $jobTrack->job_instances_id) }}"
                            v-if="jobInstance.type == 'import'"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            @lang('admin::app.settings.data-transfer.tracker.run-again')
                        </a>
                    </div>
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.completed.after', $jobTrack) !!}
                </div>
