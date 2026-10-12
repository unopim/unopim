                <div
                    class="p-5"
                    v-else-if="importResource.state == 'paused'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.paused.before', $jobTrack) !!}
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <p class="flex gap-2 items-center">
                                <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                <span class="text-amber-800 dark:text-amber-300 font-semibold" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.tracker.paused-info-export')</span>
                                <span class="text-amber-800 dark:text-amber-300 font-semibold" v-else>@lang('admin::app.settings.data-transfer.tracker.paused-info')</span>
                            </p>
                            <div class="flex gap-2 flex-shrink-0 ml-4">
                                <button class="primary-button" @click="resumeImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    @lang('admin::app.settings.data-transfer.tracker.resume')
                                </button>
                                <button class="transparent-button text-red-600 border-red-300 hover:bg-red-50 dark:hover:bg-red-900" @click="cancelImport" :disabled="isActionInProgress">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @lang('admin::app.settings.data-transfer.tracker.cancel')
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-amber-800 dark:text-amber-300">@lang('admin::app.settings.data-transfer.imports.import.progress')</span>
                            <span class="text-sm font-medium text-amber-800 dark:text-amber-300">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-amber-200 dark:bg-amber-800 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border border-gray-200 dark:border-gray-700 rounded-lg divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.tracker.running-time')</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ formattedElapsed() }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.imports.import.completed-batches')</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">@{{ stats.batches.completed }} / @{{ stats.batches.total }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.data-transfer.tracker.job-id')</p>
                            <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">#@{{ importResource.id }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.paused')
                        </span>
                        <a class="transparent-button text-sm hover:dark:bg-cherry-800" href="{{ route('admin.settings.data_transfer.tracker.log.download', $jobTrack->id) }}" target="_blank">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.paused.after', $jobTrack) !!}
                </div>
