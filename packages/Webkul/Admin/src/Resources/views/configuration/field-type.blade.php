@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];

    $name = $coreConfigRepository->getNameField($nameKey);

    $repositoryOptions = $coreConfigRepository->getValueByRepository($field);

    $validations = $coreConfigRepository->getValidations($field);

    $isRequired = Str::contains($validations, 'required') ? 'required' : '';

    $channelLocaleInfo = $coreConfigRepository->getChannelLocaleInfo($field, $currentChannel->code, $currentLocale->code);

    $field['options'] = isset($field['repository']) ? ($repositoryOptions ?? []) : ($field['options'] ?? []);

    if (! empty($field['placeholder'])) {
        $field['placeholder'] = trans($field['placeholder']);
    }

    if (! empty($field['info'])) {
        $field['info'] = trans($field['info']);
    }

    $value = core()->getConfigData($nameKey) ?? ($field['default_value'] ?? '');

    // For select fields, ensure value is always a string so v-select-handler matching works
    if (($field['type'] ?? '') === 'select' && $value !== '') {
        $value = (string) $value;
    }
@endphp


<v-configurable
    channel-count="{{ count($channels) }}"
    current-channel="{{ $currentChannel->code }}"
    current-locale="{{ $currentLocale->code }}"
    depend-name="{{ $field['depend_name'] ?? '' }}"
    field-data="{{ json_encode($field) }}"
    info="{{ $field['info'] ?? '' }}"
    is-require="{{ $isRequired }}"
    label="@lang($field['title'])"
    name="{{ $name }}"
    src="{{ $field['src'] ?? '' }}"
    validations="{{ $validations }}"
    value="{{ $value }}"
></v-configurable>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-configurable-template"
    >
        <x-admin::form.control-group class="last:!mb-0">
            <div    
                v-if="field"
                class="flex justify-between"
            >
                <x-admin::form.control-group.label ::for="name">
                    @{{ label }} <span :class="isRequire"></span>

                    <span
                        v-if="info"
                        class="icon tooltip-icon cursor-pointer ltr:ml-1.5 rtl:mr-1.5"
                        :title="info"
                    >
                        &#9432;
                    </span>
                </x-admin::form.control-group.label>
            </div>
        
            <template v-if="field.type == 'text'">
                <x-admin::form.control-group.control
                    type="text"
                    ::id="name"
                    ::name="name"
                    ::value="value"
                    ::rules="validations"
                    ::label="label"
                    ::placeholder="placeholder"
                    @input="emitChangeEvent($event.target.value, name)"
                />
            </template>
        
            <template v-if="field.type == 'textarea'">
                <x-admin::form.control-group.control
                    type="textarea"
                    ::id="name"
                    ::name="name"
                    ::value="value"
                    ::rules="validations"
                    ::label="label"
                    ::placeholder="placeholder"
                    @input="emitChangeEvent($event.target.value, name)"
                />
            </template>

            <template v-if="field.type == 'password'">
                <x-admin::form.control-group.control
                    type="password"
                    ::id="name"
                    ::name="name"
                    ::value="maskedPassword"
                    ::rules="validations"
                    ::label="label"
                    ::placeholder="placeholder"
                    @input="emitChangeEvent($event.target.value, name)"
                />
            </template>
        
            <template v-if="field.type == 'number'">
                <x-admin::form.control-group.control
                    type="number"
                    ::id="name"
                    ::name="name"
                    ::rules="validations"
                    ::value="value"
                    ::label="label"
                    ::placeholder="placeholder"
                    ::min="field.name == 'minimum_order_amount'"
                    @input="emitChangeEvent($event.target.value, name)"
                />
            </template>

            <template v-if="field.type == 'color'">
                <v-field
                    v-slot="{ field, errors }"
                    :id="name"
                    :name="name"
                    :value="value != '' ? value : '#ffffff'"
                    :label="label"
                    :rules="validations"
                    @input="emitChangeEvent($event.target.value, name)"
                >
                    <input
                        type="color"
                        v-bind="field"
                        :class="[errors.length ? 'border border-red-500' : '']"
                        class="w-full appearance-none rounded-md border text-sm text-gray-600 transition-all hover:border-gray-400 dark:text-gray-300 dark:hover:border-gray-400"
                    />
                </v-field>
            </template>

            <template v-if="field.type == 'select'">
                <x-admin::form.control-group.control
                    type="select"
                    ::id="name"
                    ::name="name"
                    ::rules="validations"
                    ::options="options"
                    ::value="value"
                    ::label="label"
                    track-by="value"
                    label-by="title"
                    @input="emitChangeEvent($event, name)"
                >
                </x-admin::form.control-group.control>
            </template>

            <template v-if="field.type == 'boolean'">
                <input
                    type="hidden"
                    :name="name"
                    :value="0"
                />
        
                <label class="relative inline-flex cursor-pointer items-center">
                    <input  
                        type="checkbox"
                        :name="name"
                        :value="1"
                        :id="name"
                        class="peer sr-only"
                        :checked="parseInt(value || 0)"
                        @input="emitChangeEvent($event.target.checked ? '1' : '0', name)"
                    >

                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-700"></div>
                </label>
            </template>

            <v-error-message
                :name="name"
                v-slot="{ message }"
            >
                <p
                    class="mt-1 text-xs italic text-red-600"
                    v-text="message"
                >
                </p>
            </v-error-message>
        </x-admin::form.control-group>
    </script>
    <script type="module">
        app.component('v-configurable', {
            template: '#v-configurable-template',
            props: [
                'channelCount',
                'currentChannel',
                'currentLocale',
                'dependName',
                'fieldData',
                'info',
                'isRequire',
                'label',
                'name',
                'src',
                'validations',
                'value',
            ],

            data() {
                return {
                    field: JSON.parse(this.fieldData),
                };
            },

            computed: {
                options() {
                    return JSON.stringify(this.field.options);
                },

                maskedPassword() {
                    return this.value ? '********' : '';
                },

                placeholder() {
                    return this.field.placeholder || '';
                },
            },

            methods: {
                emitChangeEvent(value, fieldName) {
                    this.$emitter.emit('config-value-changed', { fieldName, value: value });
                },
            },
        });
    </script>
@endPushOnce
