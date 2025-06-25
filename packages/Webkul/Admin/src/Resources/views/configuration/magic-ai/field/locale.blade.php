@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
$nameKey = $item['key'] . '.' . $field['name'];
$name = $coreConfigRepository->getNameField($nameKey);
$value = core()->getConfigData($nameKey);
$channel = core()->getConfigData('general.magic_ai.translation.source_channel');
@endphp

<v-translation-locale
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value='@json($value)'
    channel="{{ $channel }}">
</v-translation-locale>

@pushOnce('scripts')
<script type="text/x-template" id="v-translation-locale-template">
    <div class="grid gap-2.5 content-start">
            <x-admin::form.control-group class="last:!mb-0 w-full" v-if="localeOption">
                <x-admin::form.control-group.label>
                    @{{ label }}
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="select"
                    ::id="name"
                    ::name="name"
                    rules="required"
                    ref="localelRef"
                    ::label="label"
                    ::value="value"
                    ::options="localeOption"
                    @input="emitChangeEvent($event)"
                />
                <x-admin::form.control-group.error ::control-name="name" />
            </x-admin::form.control-group>
    </div>

</script>
<script type="module">
    app.component('v-translation-locale', {
        template: '#v-translation-locale-template',
        props: [
            'label',
            'name',
            'value',
            'channel'
        ],
        data() {
            return {
                localeOption: null,
                value: this.value,

            }
        },
        mounted() {
            this.fetchlocales();
            this.$emitter.on('config-channel-changed', (data) => {
                try {
                    let event = data.value;
                    if (typeof event === "string" && event.trim() !== "") {
                        let parsedEvent;
                        try {
                            parsedEvent = JSON.parse(event);
                        } catch (parseError) {
                            console.error('Failed to parse event JSON:', parseError);
                            return;
                        }

                        const channelId = parsedEvent && parsedEvent.id ? parsedEvent.id : '';

                        this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                                params: {
                                    channel: channelId
                                }
                            })
                            .then((response) => {
                                const options = response.data?.locales;
                                this.localeOption = JSON.stringify(options);
                                if (this.$refs['localelRef']) {
                                    this.$refs['localelRef'].selectedValue = null;
                                }

                                if (options.length === 1) {
                                    this.sourceLocale = options[0].id;
                                    if (this.$refs['localelRef']) {
                                        this.$refs['localelRef'].selectedValue = options[0];
                                    }
                                }
                            })
                            .catch((error) => {
                                console.error('Error fetching locales:', error);
                            });
                    } else {
                        this.localeOption = null;
                    }
                } catch (error) {
                    console.error('Unexpected error in getLocale:', error);
                }
            });
        },

        methods: {
            fetchlocales() {
                const channelId = this.channel;
                this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                        params: {
                            channel: channelId
                        }
                    })
                    .then((response) => {
                        const options = response.data?.locales;
                        this.localeOption = JSON.stringify(options);
                        if (this.$refs['localelRef']) {
                            this.$refs['localelRef'].selectedValue = null;
                        }

                        if (options.length === 1) {
                            this.sourceLocale = options[0].id;
                            if (this.$refs['localelRef']) {
                                this.$refs['localelRef'].selectedValue = options[0];
                            }
                        }
                    })
                    .catch((error) => {
                        console.error('Error fetching locales:', error);
                    });
            },

            emitChangeEvent(event) {
                this.$emitter.emit('source-locale-changed', event);
            }
        }
    });
</script>
@endPushOnce
