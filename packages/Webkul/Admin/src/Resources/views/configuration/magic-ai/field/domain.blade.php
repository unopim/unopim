@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey);
@endphp

<v-domain-model
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value='@json($value)'>
</v-domain-model>

@pushOnce('scripts')
    <script type="text/x-template" id="v-domain-model-template">
        <div class="grid gap-2.5 content-start">
            <div>
                <x-admin::form.control-group class="mb-4" >
                    <x-admin::form.control-group.label>
                        @{{ label }}
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        ::id="name"
                        ::name="name"
                        rules="required"
                        v-model="api_domain"
                        ::label="label"
                        ::placeholder="label"
                        track-by="id"
                        label-by="label"
                        @input="emitChangeEvent($event.target.value, name)"
                    />
                    <x-admin::form.control-group.error ::control-name="name" />
                </x-admin::form.control-group>
            </div>
        </div>
    </script>
    <script type="module">
        app.component('v-domain-model', {
            template: '#v-domain-model-template',
            props: [
                'label',
                'name',
                'validations',
                'value',
            ],
            data: function() {
                return {
                    api_domain: this.value,
                }
            },
            mounted() {
                this.$emitter.on('config-value-changed', (data) => {
                    if (data.fieldName == 'general[magic_ai][settings][ai_platform]') {
                        let dom = this.parseJson(data.value)?.value;
                        if (dom == 'openai') {
                            this.api_domain = 'api.openai.com';
                        } else if (dom == 'groq') {
                            this.api_domain = 'api.groq.com';
                        } else if (dom == 'gemini') {
                            this.api_domain = 'generativelanguage.googleapis.com';
                        } else {
                            this.api_domain = 'localhost';
                        }

                        this.$emitter.emit('config-value-changed', {
                            fieldName: 'general[magic_ai][settings][api_domain]',
                            value: this.api_domain
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
