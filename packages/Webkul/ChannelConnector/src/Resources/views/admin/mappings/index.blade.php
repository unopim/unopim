<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.mappings.index.title') - {{ $connector->name }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.mappings.index.title') - {{ $connector->name }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.channel_connector.connectors.edit', $connector->code) }}" class="transparent-button">
                @lang('channel_connector::app.general.back')
            </a>
        </div>
    </div>

    <div class="mt-3.5">
        <v-mapping-editor
            connector-code="{{ $connector->code }}"
            save-url="{{ route('admin.channel_connector.mappings.store', $connector->code) }}"
            :initial-mappings='@json($mappings)'
            :suggestions='@json($suggestions)'
        ></v-mapping-editor>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-mapping-editor-template">
            <div>
                <form :action="saveUrl" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @lang('channel_connector::app.mappings.fields.unopim-attribute') &rarr; @lang('channel_connector::app.mappings.fields.channel-field')
                            </p>
                            <button type="button" class="secondary-button" @click="addMapping">+ @lang('channel_connector::app.mappings.actions.add')</button>
                        </div>

                        <div v-if="suggestions.length && mappings.length === 0" class="mb-4 rounded bg-blue-50 p-3 dark:bg-blue-900/20">
                            <p class="mb-2 text-sm text-blue-700 dark:text-blue-300">@lang('channel_connector::app.mappings.auto-suggest')</p>
                            <button type="button" class="text-sm text-blue-600 underline dark:text-blue-400" @click="applySuggestions">@lang('channel_connector::app.mappings.actions.apply-suggestions')</button>
                        </div>

                        <div v-for="(mapping, index) in mappings" :key="index" class="mb-3 flex items-center gap-3 rounded border border-gray-200 p-3 dark:border-gray-700">
                            <div class="flex-1">
                                <input type="text" :name="'mappings[' + index + '][unopim_attribute_code]'" v-model="mapping.unopim_attribute_code" placeholder="@lang('channel_connector::app.mappings.fields.unopim-attribute')" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            </div>

                            <span class="text-gray-400">&rarr;</span>

                            <div class="flex-1">
                                <input type="text" :name="'mappings[' + index + '][channel_field]'" v-model="mapping.channel_field" placeholder="@lang('channel_connector::app.mappings.fields.channel-field')" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            </div>

                            <div class="w-32">
                                <select :name="'mappings[' + index + '][direction]'" v-model="mapping.direction" class="w-full rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    <option value="export">@lang('channel_connector::app.mappings.direction.export')</option>
                                    <option value="import">@lang('channel_connector::app.mappings.direction.import')</option>
                                    <option value="both">@lang('channel_connector::app.mappings.direction.both')</option>
                                </select>
                            </div>

                            <span v-if="mapping.is_translatable" class="rounded bg-purple-100 px-2 py-0.5 text-xs text-purple-700 dark:bg-purple-900 dark:text-purple-300">@lang('channel_connector::app.mappings.translatable')</span>

                            <button type="button" @click="removeMapping(index)" class="text-red-500 hover:text-red-700">&times;</button>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="primary-button">@lang('channel_connector::app.general.save')</button>
                        </div>
                    </div>
                </form>
            </div>
        </script>

        <script type="module">
            app.component('v-mapping-editor', {
                template: '#v-mapping-editor-template',
                props: ['connectorCode', 'saveUrl', 'initialMappings', 'suggestions'],
                data() {
                    return {
                        mappings: this.initialMappings?.length ? [...this.initialMappings] : [],
                    };
                },
                methods: {
                    addMapping() {
                        this.mappings.push({ unopim_attribute_code: '', channel_field: '', direction: 'export', is_translatable: false });
                    },
                    removeMapping(index) {
                        this.mappings.splice(index, 1);
                    },
                    applySuggestions() {
                        this.mappings = this.suggestions.map(s => ({ ...s }));
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
