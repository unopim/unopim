@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')

@php
    $nameKey = $item['key'] . '.' . $field['name'];
    $name = $coreConfigRepository->getNameField($nameKey);
    $channel = core()->getConfigData('general.magic_ai.translation.source_channel');
    $sourceLocale = core()->getConfigData('general.magic_ai.translation.source_locale');
    $targetChannel = core()->getConfigData('general.magic_ai.translation.target_channel');
    $selectedOptions = core()->getConfigData($nameKey);
    $targetlocales = json_encode(explode(',', $selectedOptions) ?? []);
@endphp

<v-translation-target-locale
    label="@lang($field['title'])"
    name="{{ $name }}"
    :target-locales="{{$targetlocales}}"
    :source-locale="'{{$sourceLocale}}'"
    :target-channel="'{{$targetChannel}}'"
    channel="{{ $channel }}">
</v-translation-target-locale>

@pushOnce('scripts')
    <script type="text/x-template" id="v-translation-target-locale-template">
        <div class="grid gap-2.5 content-start">
            <x-admin::form.control-group class="last:!mb-0 w-full" v-if="localeOption">
                <x-admin::form.control-group.label ::class="isTranslationEnabled ? 'required' : ''">
                    @{{ label }}
                </x-admin::form.control-group.label>
                <x-admin::form.control-group.control
                    type="multiselect"
                    ::id="name"
                    ::name="name"
                    ::rules="{ 'required': isTranslationEnabled }"
                    ref="localeRef"
                    ::label="label"
                    ::value="selectedTargets"
                    ::options="localeOption"
                />
                <x-admin::form.control-group.error ::control-name="name" />
            </x-admin::form.control-group>
        </div>
    </script>
    <script type="module">
        app.component('v-translation-target-locale', {
            template: '#v-translation-target-locale-template',
            props: [
                'label',
                'name',
                'targetLocales',
                'channel',
                'sourceLocale',
                'targetChannel',
            ],
            data() {
                return {
                    localeOption: null,
                    selectedTargets: this.targetLocales,
                    savedChannel: this.targetChannel,
                    savedTargets: this.targetLocales,
                    localeSource: this.sourceLocale,
                    sourceChannel: this.channel,
                    channelTarget: this.targetChannel,
                    isTranslationEnabled: Boolean('{{ core()->getConfigData("general.magic_ai.translation.enabled") == 1 }}'),
                }
            },
            mounted() {
                this.fetchlocales();

                this.$emitter.on('config-value-changed', (data) => {
                    if (data.fieldName == 'general[magic_ai][translation][enabled]') {
                        this.isTranslationEnabled = parseInt(data.value || 0) === 1;
                    }
                });
                
                this.$emitter.on('source-channel-changed', (data) => {
                    if (data) {
                        this.sourceChannel = JSON.parse(data).id;
                        this.$refs['localeRef'].selectedValue = null;
                    }
                });
                this.$emitter.on('source-locale-changed', (data) => {
                    if (data) {
                        this.localeSource = JSON.parse(data).id;
                        this.fetchlocales(true);
                    }
                });
                this.$emitter.on('config-target-channel-changed', (data) => {
                    if (data) {
                        const parsedData = JSON.parse(data).id;
                        this.channelTarget = parsedData;
                        this.fetchlocales(true);
                    }
                });

            },

            methods: {
                fetchlocales(resetSelection = false) {
                    if (! this.channelTarget) {
                        this.localeOption = '[]';

                        return;
                    }

                    const channelId = this.channelTarget;

                    const currentCodes = (this.$refs['localeRef']?.selectedValue ?? [])
                        .map(option => (option && option.id) ? option.id : option)
                        .filter(Boolean);

                    this.$axios.get("{{ route('admin.catalog.product.get_locale') }}", {
                            params: {
                                channel: channelId
                            }
                        })
                        .then((response) => {
                            let options = [];
                            if (this.sourceChannel === this.channelTarget) {
                                options = response.data?.locales.filter(option => option.id != this.localeSource);
                            } else {
                                options = response.data?.locales;
                            }

                            const savedCodes = this.channelTarget === this.savedChannel
                                ? (Array.isArray(this.savedTargets) ? this.savedTargets : String(this.savedTargets ?? '').split(','))
                                : [];

                            let selected = options.filter(option => currentCodes.includes(option.id));

                            if (! selected.length) {
                                selected = options.filter(option => savedCodes.includes(option.id));
                            }

                            if (! selected.length && options.length == 1) {
                                selected = options;
                            }

                            this.selectedTargets = selected.length
                                ? JSON.stringify(selected.map(option => option.id))
                                : '';

                            this.localeOption = JSON.stringify(options);

                            if (resetSelection && this.$refs['localeRef']) {
                                this.$refs['localeRef'].selectedValue = null;
                            }

                            if (selected.length && this.$refs['localeRef']) {
                                this.$refs['localeRef'].selectedValue = selected;
                            }
                        })
                        .catch((error) => {
                            console.error('Error fetching locales:', error);
                        });
                }
            }
        });
    </script>
@endPushOnce
