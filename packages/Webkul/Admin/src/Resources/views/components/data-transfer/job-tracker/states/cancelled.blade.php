                <div
                    class="p-5"
                    v-else-if="importResource.state == 'cancelled'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.cancelled.before', $jobTrack) !!}
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            <p class="font-semibold text-red-800 dark:text-red-300" v-if="jobInstance.type == 'export'">@lang('admin::app.settings.data-transfer.tracker.cancelled-info-export')</p>
                            <p class="font-semibold text-red-800 dark:text-red-300" v-else>@lang('admin::app.settings.data-transfer.tracker.cancelled-info')</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">@lang('admin::app.settings.data-transfer.imports.import.progress')</span>
                            <span class="text-sm font-medium text-red-800 dark:text-red-300">@{{ stats.progress }}%</span>
                        </div>
                        <div class="w-full bg-red-200 dark:bg-red-800 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" :style="{ 'width': stats.progress + '%' }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-4">
                        <div class="p-3 bg-white dark:bg-cherry-900" v-for="(value, key) in summary" :key="key">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@{{ key }}</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(value).toLocaleString() }}</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700" v-if="importResource.started_at">
                            <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('admin::app.settings.data-transfer.tracker.total-duration')</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">@{{ totalDuration() }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.cancelled')
                        </span>
                        <a class="transparent-button text-sm hover:dark:bg-cherry-800" href="{{ route('admin.settings.data_transfer.tracker.log.download', $jobTrack->id) }}" target="_blank">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            @lang('admin::app.settings.data-transfer.tracker.download-log-file')
                        </a>
                    </div>
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.cancelled.after', $jobTrack) !!}
                </div>
