@props([
    'fetchUrl' => '',
    'columns'  => [],
    'rows'     => []
])

@include('admin::components.bulkedit.header')
@include('admin::components.bulkedit.header-cell')
@include('admin::components.bulkedit.row')
@include('admin::components.bulkedit.cell')
@include('admin::components.bulkedit.grid')
@include('admin::components.bulkedit.type.text')
@include('admin::components.bulkedit.type.textarea')
@include('admin::components.bulkedit.type.select')
@include('admin::components.bulkedit.type.multiselect')
@include('admin::components.bulkedit.type.date')
@include('admin::components.bulkedit.type.image')
@include('admin::components.bulkedit.type.gallery')
@include('admin::components.bulkedit.type.boolean')


@php
    $channelLocales = [];
    $allLocales = [];
    $channels = core()->getAllChannels();
    $channelCurrencies = [];

    foreach ($channels as $channel) {
        $channelCurrencies[$channel->code] = $channel->currencies->map(function ($currency) {
            return [
                'code'   => $currency->code,
                'symbol' => $currency->symbol,
            ];
        })->toArray();

        $locales = $channel->locales->pluck('code')->toArray();
        $channelLocales[$channel->code] = $locales;
        $allLocales = array_merge($allLocales, $locales);
    }

    $allCurrencies = [];

    foreach ($channelCurrencies as $currList) {
        foreach ($currList as $curr) {
            $allCurrencies[$curr['code']] = $curr;
        }
    }

    $allCurrencies = array_values($allCurrencies);
    $channels = $channels->pluck('code')->toArray();
    $allLocales = array_unique($allLocales);

    $headers = [];
    $finalColumns = [];

    foreach ($columns as $index => $col) {
        $isPrice = isset($col['type']) && $col['type'] === 'price';
        $label = !empty($col['name']) ? $col['name'] : $col['code'];

        if ($isPrice && $col['value_per_channel'] && $col['value_per_locale']) {
            foreach ($channels as $channelCode) {
                $currencies = $channelCurrencies[$channelCode];
                $locales = $channelLocales[$channelCode];

                foreach ($currencies as $currency) {
                    foreach ($locales as $locale) {
                        $headers[] = [
                            'label' => "{$label} - {$channelCode} - {$currency['code']} - {$locale}",
                        ];
                        $finalColumns[] = [
                            'id'       => $index,
                            'code'     => $col['code'],
                            'type'     => $col['type'],
                            'channel'  => $channelCode,
                            'currency' => $currency['code'],
                            'locale'   => $locale,
                            'key'      => 'pcl',
                        ];
                    }
                }
            }

        } elseif ($isPrice && $col['value_per_channel']) {
            foreach ($channels as $channelCode) {
                foreach ($channelCurrencies[$channelCode] as $currency) {
                    $headers[] = [
                        'label' => "{$label} - {$channelCode} - {$currency['code']}",
                    ];
                    $finalColumns[] = [
                        'id'       => $index,
                        'code'     => $col['code'],
                        'type'     => $col['type'],
                        'channel'  => $channelCode,
                        'currency' => $currency['code'],
                        'key'      => 'pc',
                    ];
                }
            }

        } elseif ($isPrice && $col['value_per_locale']) {
            foreach ($allCurrencies as $currency) {
                foreach ($allLocales as $locale) {
                    $headers[] = [
                        'label' => "{$label} - {$currency['code']} - {$locale}",
                    ];
                    $finalColumns[] = [
                        'id'       => $index,
                        'code'     => $col['code'],
                        'type'     => $col['type'],
                        'currency' => $currency['code'],
                        'locale'   => $locale,
                        'key'      => 'pl',
                    ];
                }
            }

        } elseif ($isPrice) {
            foreach ($allCurrencies as $currency) {
                $headers[] = [
                    'label' => "{$label} - {$currency['code']}",
                ];
                $finalColumns[] = [
                    'id'       => $index,
                    'code'     => $col['code'],
                    'type'     => $col['type'],
                    'currency' => $currency['code'],
                    'key'      => 'p',
                ];
            }

        } elseif ($col['value_per_channel'] && $col['value_per_locale']) {
            foreach ($channels as $channelCode) {
                foreach ($channelLocales[$channelCode] as $locale) {
                    $headers[] = [
                        'label' => "{$label} - {$channelCode} - {$locale}",
                    ];
                    $finalColumns[] = [
                        'id'       => $index,
                        'code'     => $col['code'],
                        'type'     => $col['type'],
                        'channel'  => $channelCode,
                        'locale'   => $locale,
                        'key'      => 'cl',
                    ];
                }
            }

        } elseif ($col['value_per_channel']) {
            foreach ($channels as $channelCode) {
                $headers[] = [
                    'label' => "{$label} - {$channelCode}",
                ];
                $finalColumns[] = [
                    'id'       => $index,
                    'code'     => $col['code'],
                    'type'     => $col['type'],
                    'channel'  => $channelCode,
                    'key'      => 'c',
                ];
            }

        } elseif ($col['value_per_locale']) {
            foreach ($allLocales as $locale) {
                $headers[] = [
                    'label' => "{$label} - {$locale}",
                ];
                $finalColumns[] = [
                    'id'       => $index,
                    'code'     => $col['code'],
                    'type'     => $col['type'],
                    'locale'   => $locale,
                    'key'      => 'l',
                ];
            }

        } else {
            $headers[] = [
                'label' => $label,
            ];
            $finalColumns[] = [
                'id'       => $index,
                'code'     => $col['code'],
                'type'     => $col['type'],
                'key'      => '',
            ];
        }
    }
@endphp

<x-admin::form.control-group.control type="hidden"/>

<v-spreadsheet-editor
    fetch-url="{{ $fetchUrl }}"
    entity-save-url="{{ route('admin.catalog.products.bulk-edit.save') }}"

    :columns="{{ json_encode($columns) }}"
    :headers="{{ json_encode($headers) }}"
    :initial-data="{{ json_encode($rows) }}"
    :flt-columns="{{ json_encode($finalColumns) }}"
    :all-locales="{{ json_encode($allLocales) }}"
    :channel-locales="{{ json_encode($channelLocales) }}"
    :channels="{{ json_encode($channels) }}"
></v-spreadsheet-editor>

@pushOnce('scripts')
    <script type="text/x-template" id="v-spreadsheet-editor-template">
        <div class="flex  justify-end gap-x-2.5">
            <a
                href="{{ route('admin.catalog.products.index') }}"
                class="transparent-button"
            >
                @lang('admin::app.account.edit.back-btn')
            </a>
            <button class="primary-button" @click="handleSave">@lang('admin::app.catalog.products.edit.types.configurable.edit.save-btn')</button>
        </div>

        <div class="h-[calc(100vh-150px)] mt-2 overflow-y-auto space-y-4 rounded-lg shadow-lg">
            <table class="table-fixed border-4 border-violet-50 border-collapse w-full dark:border-cherry-700">
                <v-spreadsheet-header 
                    :columns="columns" 
                    :headers="headers"
                />
                <v-spreadsheet-grid 
                    :url="fetchUrl"
                    :columns="columns"
                    :initial-data="initialData"
                    :channels="channels"
                    :channel-locales="channelLocales"
                    :locales="allLocales"
                    :fltColumns="fltColumns"
                    sku-unique-url="{{ route('admin.catalog.products.bulk-edit.check-sku') }}"
                />
            </table>
        </div>
    </script>

    <script type="module">
        app.component('v-spreadsheet-editor', {
            template: '#v-spreadsheet-editor-template',

            props: {
                columns: {
                    type: Array,
                    default: () => []
                },
                headers: {
                    type: Array,
                    default: () => []
                },
                initialData: {
                    type: Array,
                    default: () => []
                },
                fetchUrl: {
                    type:String
                },
                entitySaveUrl: {
                    type:String,
                },
                allLocales: {
                    type: Array,
                    default: () => []
                },
                channels: {
                    type: Array,
                    default: () => []
                },
                channelLocales: {
                    type: Object,
                    default: () => ({})
                },
                fltColumns: {
                    type: Object,
                    default: () => ({})
                },
            },

            data() {
                return {
                    allRows: this.initialRows || [],
                    rowsPerPage: 100,
                    currentPage: 1,
                    isLoading: false,
                    updatedEntityData: {},
                };
            },

            created() {
                this.registerGlobalEvents();
            },

            methods: {
                registerGlobalEvents() {
                    this.$emitter.on('update-spreadsheet-data', (data) => {
                        this.updateEntityData(data);
                    });
                },

                updateEntityData({ value, entityId, column }) {
                    const { code, channel, locale, currency } = column;
                    if (! this.updatedEntityData[entityId]) {
                        this.updatedEntityData[entityId] = {};
                    }

                    let data = this.updatedEntityData[entityId];
                    let data2 = data;

                    const keys = [code];

                    if (channel) {
                        keys.push(channel);
                    }
                    if (locale) {
                        keys.push(locale);
                    }
                    if (currency) {
                        keys.push(currency);
                    }

                    for (let i = 0; i < keys.length - 1; i++) {
                        const key = keys[i];
                        data[key] ||= {};
                        data = data[key];
                    }

                    data[keys[keys.length - 1]] = value;
                },

                handleSave() {
                    if (Object.keys(this.updatedEntityData).length === 0) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.catalog.products.bulk-edit.no-changes')",
                        });
                        return;
                    }

                    this.loading = true;

                    this.$axios.post(this.entitySaveUrl, {
                        data: this.updatedEntityData,
                    })
                    .then(response => {
                        this.updatedEntityData = [];
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message ||  "@lang('admin::app.catalog.products.bulk-edit.success')",
                        });

                        setTimeout(() => window.location.href= "{{ route('admin.catalog.products.index') }}", 1000);
                    })
                    .catch(error => {
                        console.error(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
                },
            },
        });
    </script>

@endPushOnce
