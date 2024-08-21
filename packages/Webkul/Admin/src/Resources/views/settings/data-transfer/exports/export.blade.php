<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.exports.export.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            <a
                href="{{ route('admin.settings.data_transfer.exports.index') }}"
                class="text-gray-600 hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white"
            >
                @lang('admin::app.settings.data-transfer.exports.export.title')
            </a>
             - {{ ucfirst(strtolower(__($export->entity_type))) }} / {{ ucfirst(strtolower(__($export->code))) }}
            
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Cancel Button -->
            <a
                href="{{ route('admin.settings.data_transfer.exports.index') }}"
                class="transparent-button"
            >
                @lang('admin::app.settings.data-transfer.exports.export.back-btn')
            </a>

            <!-- Save Button -->
            <a
                href="{{ route('admin.settings.data_transfer.exports.edit', $export->id) }}"
                class="primary-button"
            >
                @lang('admin::app.settings.data-transfer.exports.export.edit-btn')
            </a>
        </div>
    </div>

    <!-- Import Vue Compontent -->
    <v-export-now />

    @pushOnce('scripts')
        <script type="text/x-template" id="v-export-now-template">
            <!-- Body Content -->
            <div class="grid gap-2.5 mt-3.5 p-5 max-xl:flex-wrap box-shadow">
                <div
                    class="flex flex-col gap-1.5 w-full p-3 border border-orange-200 rounded-sm"
                >
                    <p class="flex gap-2 items-center dark:text-white">
                        <i class="text-2xl text-orange-600 dark:text-orange-600! bg-orange-200 rounded-full"></i>

                        <span class="font-bold">
                            @lang('admin::app.settings.data-transfer.exports.export.profile') :  
                        </span>

                        <span class="text-gray-600 dark:text-white">
                            {{ $export->code }}
                        </span>
                    </p>
                    <p class="flex gap-2 items-center dark:text-white">
                        <i class="text-2xl text-orange-600 dark:text-orange-600! bg-orange-200 rounded-full"></i>

                        <span class="font-bold">
                            @lang('admin::app.settings.data-transfer.exports.export.file-format') :  
                        </span>

                        <span class="text-gray-600 dark:text-white">
                            {{ $export->filters['file_format'] }}
                        </span>
                    </p>
                    <p class="flex gap-2 items-center dark:text-white">
                        <i class="text-2xl text-orange-600 dark:text-orange-600! bg-orange-200 rounded-full"></i>

                        <span class="font-bold">
                            @lang('admin::app.settings.data-transfer.exports.export.with-media') :  
                        </span>

                        <span class="text-gray-600 dark:text-white">
                            {{ $export->filters['with_media'] == 1 ? 'Yes' : 'No' }}
                        </span>
                    </p>
                </div>
                @if (bouncer()->hasPermission('data_transfer.export.execute'))
                    <x-admin::form  
                            :action="route('admin.settings.data_transfer.exports.export_now', ['id' => $export->id])"
                        >
                        @method('PUT')

                        {!! view_render_event('unopim.admin.settings.channels.edit.edit_form_controls.before') !!}
                            <button
                                type="submit" 
                                class="primary-button place-self-start"
                                aria-lebel="Submit"
                            >
                                @lang('admin::app.settings.data-transfer.exports.export.export-now')
                            </button>
                        {!! view_render_event('unopim.admin.settings.channels.edit.edit_form_controls.after') !!}

                    </x-admin::form>
                @endif
            </div>
        </script>

        <script type="module">
            app.component('v-export-now', {
                template: '#v-export-now-template',

                data() {
                    return {
                        exportResource: @json($export), 
                    };
                },
            })
        </script>
    @endPushOnce
</x-admin::layouts>