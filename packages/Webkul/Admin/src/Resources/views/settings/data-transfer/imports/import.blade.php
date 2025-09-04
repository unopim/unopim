<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.data-transfer.imports.import.title')
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            <a
                href="{{ route('admin.settings.data_transfer.imports.index') }}"
                class="text-gray-600 hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white"
            >
                @lang('admin::app.settings.data-transfer.imports.import.title')
            </a>
             - {{ (ucfirst(strtolower($import->entity_type)))."/".(ucfirst(strtolower($import->code))) }}            
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Cancel Button -->
            <a
                href="{{ route('admin.settings.data_transfer.imports.index') }}"
                class="transparent-button"
            >
                @lang('admin::app.settings.data-transfer.imports.import.back-btn')
            </a>

            <!-- Save Button -->
            <a
                href="{{ route('admin.settings.data_transfer.imports.edit', $import->id) }}"
                class="primary-button"
            >
                @lang('admin::app.settings.data-transfer.imports.import.edit-btn')
            </a>
        </div>
    </div>

    <!-- Import Vue Compontent -->
    <v-import-now />

    @pushOnce('scripts')
        <script type="text/x-template" id="v-import-now-template">
            <!-- Body Content -->
            <div class="grid gap-2.5 mt-3.5 p-5 max-xl:flex-wrap box-shadow">
                <div
                    class="flex flex-col gap-1.5 w-full p-3 border border-orange-200 rounded-sm"
                >
                    <p class="flex gap-2 items-center dark:text-white">
                        <i class="text-2xl text-orange-600 dark:text-orange-600! bg-orange-200 rounded-full"></i>

                        <span class="font-bold">
                            @lang('admin::app.settings.data-transfer.imports.import.profile') :  
                        </span>

                        <span class="text-gray-600 dark:text-white">
                            {{ $import->code }}
                        </span>
                    </p>

                    @if (! empty($import->file_path))
                    <p class="flex gap-2 items-center dark:text-white">
                        <i class="text-2xl text-orange-600 dark:text-orange-600! bg-orange-200 rounded-full"></i>
                        <span class="font-bold">
                            @lang('admin::app.settings.data-transfer.imports.import.file_path') :  
                        </span>
                        <span class="text-gray-600 dark:text-white">
                            {{ $import->file_path }}
                        </span>
                    </p>
                    @endif

                    <p class="flex gap-2 items-center dark:text-white">
                        <i class="text-2xl text-orange-600 dark:text-orange-600! bg-orange-200 rounded-full "></i>

                        <span class="font-bold">
                            @lang('admin::app.settings.data-transfer.imports.import.action') :  
                        </span>

                        <span class="text-gray-600 dark:text-white">
                            @php
                                $actionConfig = collect(config('import_settings.actions'))->firstWhere('id', $import->action);
                                $actionTitle = $actionConfig ? __($actionConfig['title']) : __($import->action);
                            @endphp

                            {{ $actionTitle }}
                        </span>
                    </p>
                </div>
                @if (bouncer()->hasPermission('data_transfer.imports.execute'))
                    <x-admin::form  
                            :action="route('admin.settings.data_transfer.imports.import_now', ['id' => $import->id])"
                        >

                        @method('PUT')

                        {!! view_render_event('unopim.admin.settings.channels.edit.edit_form_controls.before') !!}
                            <button
                                type="submit" 
                                class="primary-button place-self-start"
                                aria-lebel="Submit"
                            >
                                @lang('admin::app.settings.data-transfer.imports.import.import-now')
                            </button>
                        {!! view_render_event('unopim.admin.settings.channels.edit.edit_form_controls.after') !!}

                    </x-admin::form> 
                @endif
            </div>
        </script>

        <script type="module">
            app.component('v-import-now', {
                template: '#v-import-now-template',

                data() {
                    return {
                        importResource: @json($import),
                    };
                },
            })
        </script>
    @endPushOnce
</x-admin::layouts>
