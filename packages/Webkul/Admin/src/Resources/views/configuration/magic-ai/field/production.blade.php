@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey);
    $api_platform = core()->getConfigData('general.magic_ai.settings.ai_platform');
@endphp

<v-production-model
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value="'{{$value}}'"
    api_plateform={{$api_platform}}>
</v-production-model>

@pushOnce('scripts')
<script type="text/x-template" id="v-production-model-template">
    <div class="grid gap-2.5 content-start">
        <div>
                <template v-if="plateform === 'openai' || show">
                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label>
                            @{{ label }}
                        </x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            ::id="name"
                            ::name="name"
                            rules="required"
                            v-model="value"
                            ::label="label"
                            ::placeholder="label"
                            track-by="id"
                            label-by="label"
                            @input="emitChangeEvent($event.target.value, name)"
                        />
                        <x-admin::form.control-group.error ::control-name="name" />
                    </x-admin::form.control-group>
                </template>

        </div>
    </div>

</script>
<script type="module">
    app.component('v-production-model', {
        template: '#v-production-model-template',
        props: [
            'label',
            'name',
            'validations',
            'value',
            'api_plateform'
        ],
        data: function() {
            return {
                value: this.value,
                show: false,
                plateform: this.api_plateform
            }
        },
        mounted() {
            this.$emitter.on('config-value-changed', (data) => {
                if (data.fieldName == 'general[magic_ai][settings][ai_platform]') {
                    let dom = this.parseJson(data.value)?.value;

                    if (dom == 'openai') {
                        this.show = true;
                    } else {
                        this.show = false;
                        this.plateform = 'other';
                    }

                    this.$emitter.emit('config-value-changed', {
                        fieldName: 'general[magic_ai][settings][api_domain]',
                        value: this.value
                    });
                }
            });
        },

        methods: {
            parseJson(value) {
                try {
                    return JSON.parse(value);
                } catch (e) {
                    return null;
                }
            },
            emitChangeEvent(value, fieldName) {

                this.$emitter.emit('config-value-changed', {
                    fieldName,
                    value: value
                });
            },


        }
    });
</script>
@endPushOnce
