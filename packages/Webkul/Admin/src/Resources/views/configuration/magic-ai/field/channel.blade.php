@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $value = core()->getConfigData($nameKey);
@endphp

<v-translation-channel
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value='@json($value)'>
</v-translation-channel>

@pushOnce('scripts')
    <script type="text/x-template" id="v-translation-channel-template">
        <div class="grid gap-2.5 content-start ">
            <x-admin::form.control-group class="last:!mb-0 w-full" >
                <x-admin::form.control-group.label>
                        @{{ label }}
                </x-admin::form.control-group.label>
                @php
                    $channels = core()->getAllChannels();
                    $options = [];
                    foreach($channels as $channel)
                    {
                        $options[] = [
                            'id' => $channel->code,
                            'label' => $channel->name,
                            ];
                        }
                @endphp
                <x-admin::form.control-group.control
                    type="select"
                    ::id="name"
                    ::name="name"
                    rules="required"
                    v-model="sourceChannel"
                    ::label="label"
                    :options="json_encode($options)"
                    ::value="value"
                    track-by="id"
                    label-by="label"
                    @input="emitChangeEvent($event)"
                />
                <x-admin::form.control-group.error ::control-name="name" />
            </x-admin::form.control-group>
        </div>
    </script>
    <script type="module">
        app.component('v-translation-channel', {
            template: '#v-translation-channel-template',
            props: [
                'label',
                'name',
                'validations',
                'value',
            ],
            data: function() {
                return {

                }
            },

            methods: {
                parseJson(value) {
                    try {
                        return JSON.parse(value);
                    } catch (e) {
                        return null;
                    }
                },
                emitChangeEvent(value) {
                    this.$emitter.emit('config-channel-changed', {
                        value: value
                    });
                    this.$emitter.emit('source-channel-changed',value);
                },
            }
        });
    </script>
@endPushOnce
