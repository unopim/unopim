                <div
                    class="p-5"
                    v-else-if="importResource.state == 'failed'"
                >
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.failed.before', $jobTrack) !!}
                    <div class="rounded-lg p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="flex gap-2 items-center text-red-800 dark:text-red-300 font-semibold">
                            <i class="icon-cancel h-fit rounded-full bg-red-200 dark:bg-red-800 text-2xl text-red-600 dark:text-red-200"></i>
                            @lang('admin::app.settings.data-transfer.tracker.failed-info')
                        </p>
                        <div class="grid gap-1 ml-8 mt-2" v-if="importResource.errors?.length">
                            <p class="break-all text-sm text-red-600 dark:text-red-400" v-for="(error, index) in importResource.errors" :key="index">@{{ error }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            @lang('admin::app.settings.data-transfer.tracker.index.datagrid.failed')
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
                    {!! view_render_event('unopim.admin.settings.data_transfer.tracker.job.state.failed.after', $jobTrack) !!}
                </div>
