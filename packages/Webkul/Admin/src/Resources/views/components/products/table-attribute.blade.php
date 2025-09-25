@props([
    'value' => [],
    'columns' => [],
    'fieldName' => '',
    'locale' => null,
])

<v-table-attribute 
    locale="{{ $locale }}" 
    field-name="{{ $fieldName }}" 
    :value="{{ json_encode($value) }}" 
    :columns="{{ json_encode($columns) }}" 
/>

@pushOnce('scripts')
    <script type="text/x-template" id="v-table-attribute-template">
        <div class="overflow-auto w-full">
            <x-admin::modal ref="imagePreviewModal">
                <x-slot:header>
                    <p class="text-lg text-gray-800 dark:text-white font-bold"></p>
                </x-slot>
                <x-slot:content>
                    <div style="max-width: 100%; height: 260px;">
                        <img :src="fileUrl" class="w-full h-full object-contain object-top" />
                    </div>
                </x-slot>
            </x-admin::modal>

            <table class="w-full text-sm text-left border rounded-md border-slate-300 dark:border-gray-600 divide-y divide-gray-100">
                <x-admin::table.thead class="text-sm bg-violet-100 font-medium dark:bg-gray-800">
                    <x-admin::table.thead.tr>
                        <x-admin::table.th v-for="column in columns">
                            <p v-text="getTranslation(column, locale)"></p>
                        </x-admin::table.th>
                        <x-admin::table.th />
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <draggable
                    v-if="columns.length !== 0"
                    tag="tbody"
                    ghost-class="draggable-ghost"
                    handle=".icon-drag"
                    :list="attributeData"
                    v-bind="{ animation: 200 }"
                >
                    <template #item="{ element, index }">
                        <x-admin::table.thead.tr
                            class="border rounded-md border-slate-300 dark:!border-gray-600 hover:bg-violet-50 dark:hover:bg-cherry-800"
                            v-show="!element.isDelete"
                        >
                            <input type="hidden" :name="fieldName + '[' + index + '][isNew]'" :value="element.isNew" />
                            <input type="hidden" :name="fieldName + '[' + index + '][isDelete]'" :value="element.isDelete" />

                            <x-admin::table.td v-for="column in columns" class="!whitespace-normal">
                                <v-field
                                    v-slot="{ field, errors }"
                                    :rules="getValidation(column)"
                                    :name="fieldName + '[' + index + '][' + column.code + ']'"
                                    :value="element[column.code]"
                                >
                                    <input
                                        v-if="['text', 'date'].includes(getType(column.type))"
                                        :type="getType(column.type)"
                                        :name="fieldName + '[' + index + '][' + column.code + ']'"
                                        v-model="element[column.code]"
                                        v-bind="field"
                                        class="py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                                    />

                                    <!-- Image Input -->
                                    <div v-if="getType(column.type) === 'image'" class="flex">
                                        <div
                                            v-if="element[column.code]?.['url']"
                                            class="justify-items-center border rounded p-1 relative overflow-hidden group"
                                            style="width: 86px; height: 46px;"
                                        >
                                            <img :src="element[column.code]['url']" class="w-full h-full object-contain object-top" />

                                            <div class="flex flex-col justify-between invisible w-full bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 group-hover:visible">
                                                <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>
                                                <div class="flex justify-between">
                                                    <span
                                                        class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                                        @click="removeImage(index, column.code)"
                                                    ></span>
                                                    <span
                                                        class="icon-view text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                                        @click="preview(index, column.code)"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>

                                        <input
                                            type="hidden"
                                            :value="element[column.code]?.['val']"
                                            :name="fieldName + '[' + index + '][' + column.code + ']'"
                                        />
                                        <input
                                            type="file"
                                            class="hidden"
                                            :id="'image_' + index + '_' + column.code"
                                            :name="fieldName + '[' + index + '][' + column.code + ']'"
                                            accept="image/*"
                                            @change="handleFileChange($event, column.code, index)"
                                        />
                                        <label
                                            v-if="!element[column.code]?.['val']"
                                            :for="'image_' + index + '_' + column.code"
                                            class="text-sm cursor-pointer text-gray-800 dark:text-gray-300"
                                        >
                                            @lang('admin::app.catalog.attributes.edit.select-image')
                                        </label>
                                    </div>

                                    <!-- Boolean Input -->
                                    <input
                                        v-if="getType(column.type) === 'boolean'"
                                        type="hidden"
                                        :name="fieldName + '[' + index + '][' + column.code + ']'"
                                        value="false"
                                    />
                                    <label
                                        v-if="getType(column.type) === 'boolean'"
                                        class="relative inline-flex items-center cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            class="sr-only peer"
                                            :name="fieldName + '[' + index + '][' + column.code + ']'"
                                            :value="true"
                                            v-bind="field"
                                            v-model="element[column.code]"
                                        />
                                        <div class="w-9 h-5 bg-gray-200 rounded-full peer dark:bg-gray-900 peer-checked:bg-violet-700 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                                    </label>

                                    <!-- Select / Multiselect -->
                                    <v-async-select-handler
                                        v-if="getType(column.type) === 'select'"
                                        :name="fieldName + '[' + index + '][' + column.code + ']'"
                                        :queryParams="{ entityName: 'attribute_column', attributeId: column.id }"
                                        v-bind="field"
                                        track-by="code"
                                        label-by="label"
                                        :class="[errors.length ? 'border border-red-500' : 'w-[147px]']"
                                        list-route="{{ route('admin.catalog.options.fetch-all') }}"
                                    />

                                    <v-async-select-handler
                                        v-if="getType(column.type) === 'multiselect'"
                                        :name="fieldName + '[' + index + '][' + column.code + ']'"
                                        :queryParams="{ entityName: 'attribute_column', attributeId: column.id }"
                                        v-bind="field"
                                        track-by="code"
                                        label-by="label"
                                        multiple
                                        :class="[errors.length ? 'border border-red-500' : 'w-[147px]']"
                                        list-route="{{ route('admin.catalog.options.fetch-all') }}"
                                    />
                                </v-field>

                                <v-error-message
                                    :name="fieldName + '[' + index + '][' + column.code + ']'"
                                    v-slot="{ message }"
                                >
                                    <p class="mt-1 text-red-600 text-xs italic" style="text-wrap: wrap;" v-text="message"></p>
                                </v-error-message>
                            </x-admin::table.td>

                            <x-admin::table.td class="!px-0">
                                <span
                                    class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-gray-800"
                                    @click="removeRow(index)"
                                ></span>
                            </x-admin::table.td>
                        </x-admin::table.thead.tr>
                    </template>
                </draggable>

                <div v-if="columns.length === 0" class="mt-1 text-orange-600 text-xs italic">
                    @lang('admin::app.catalog.attributes.edit.no-columns')
                </div>
            </table>
        </div>

        <div class="mt-2 flex justify-end">
            <div
                v-if="columns.length !== 0"
                class="cursor-pointer text-violet-700 font-semibold text-sm"
                @click="addNewRow()"
            >
                @lang('admin::app.catalog.attributes.edit.add-row')
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-table-attribute', {
            template: '#v-table-attribute-template',

            props: {
                columns: { type: Array, default: () => [] },
                value: { type: Array, default: () => [] },
                fieldName: { type: String, default: '' },
                locale: { type: String, default: '' },
            },

            data() {
                return {
                    attributeData: [],
                    fileUrl: null,
                };
            },

            mounted() {
                this.attributeData = this.value;
            },

            methods: {
                addNewRow() {
                    let newRow = [];
                    this.columns.forEach(column => {
                        newRow[column.code] = '';
                    });
                    this.attributeData.push(newRow);
                },

                preview(index, columnCode) {
                    this.fileUrl = this.attributeData[index][columnCode]['url'];
                    this.$refs.imagePreviewModal.toggle();
                },

                handleFileChange(event, columnCode, index) {
                    const file = event.target.files[0];
                    if (file) {
                        if (!file.type.startsWith('image/')) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: "@lang('admin::app.components.media.images.not-allowed-error')"
                            });

                            return;
                        }

                        let fileUrl = URL.createObjectURL(file);
                        this.attributeData[index][columnCode] = [];
                        this.attributeData[index][columnCode]['val'] = fileUrl;
                        this.attributeData[index][columnCode]['url'] = fileUrl;
                    }
                },

                removeImage(index, columnCode) {
                    this.attributeData[index][columnCode]['val'] = null;
                    this.attributeData[index][columnCode]['url'] = null;
                    const input = document.getElementById('image_' + index + '_' + columnCode);
                    if (input) input.value = '';
                },

                removeRow(index) {
                    this.attributeData[index].isDelete = true;
                },

                getTranslation(column, locale) {
                    const translation = column.translations.find(trans => trans.locale === locale);
                    return translation?.label || column.code;
                },

                getType(type) {
                    return type;
                },

                getValidation(column) {
                    let result = {};
                    const validations = column.validation;
                    if (! validations || validations.trim() === '' || column.type === 'image') return result;

                    try {
                        let rules = JSON.parse(validations);
                        if (!Array.isArray(rules)) rules = [rules];
                        rules.forEach(rule => {
                            if (rule.id === 'required') result.required = true;
                            if (rule.id === 'number') result.numeric = true;
                            if (rule.id === 'email') result.email = true;
                            if (rule.id === 'decimal') result.decimal = true;
                        });
                    } catch {
                        const items = validations.split(',').map(v => v.trim().toLowerCase());
                        if (items.includes('required')) result.required = true;
                        if (items.includes('number')) result.numeric = true;
                        if (items.includes('email')) result.email = true;
                        if (items.includes('decimal')) result.decimal = true;
                    }

                    return result;
                },
            },
        });
    </script>
@endPushOnce
