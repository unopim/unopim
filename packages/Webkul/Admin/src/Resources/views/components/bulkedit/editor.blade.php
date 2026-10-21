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
@include('admin::components.bulkedit.type.datetime')
@include('admin::components.bulkedit.type.image')
@include('admin::components.bulkedit.type.gallery')
@include('admin::components.bulkedit.type.boolean')
@includeIf('measurement::components.bulkedit.type.measurement')

<x-admin::media.image-viewer v-if="false" />


@php
    $channelLocales = [];
    $allLocales = [];
    $channelCollection = core()->getAllChannels();
    $channelCurrencies = [];
    $channelNames = [];
    $localeNames = [];

    foreach ($channelCollection as $channel) {
        $channelNames[$channel->code] = $channel->name ?: $channel->code;

        $channelCurrencies[$channel->code] = $channel->currencies->map(function ($currency) {
            return [
                'code'   => $currency->code,
                'symbol' => $currency->symbol,
            ];
        })->toArray();

        $locales = $channel->locales->pluck('code')->toArray();
        $channelLocales[$channel->code] = $locales;
        $allLocales = array_merge($allLocales, $locales);

        foreach ($channel->locales as $locale) {
            $localeNames[$locale->code] = $locale->name ?: $locale->code;
        }
    }

    $allCurrencies = [];

    foreach ($channelCurrencies as $currList) {
        foreach ($currList as $curr) {
            $allCurrencies[$curr['code']] = $curr;
        }
    }

    $allCurrencies = array_values($allCurrencies);
    $channels = $channelCollection->pluck('code')->toArray();
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
                            'label' => "{$label} - " . ($channelNames[$channelCode] ?? $channelCode) . " - {$currency['code']} - " . ($localeNames[$locale] ?? $locale),
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
                        'label' => "{$label} - " . ($channelNames[$channelCode] ?? $channelCode) . " - {$currency['code']}",
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
                        'label' => "{$label} - {$currency['code']} - " . ($localeNames[$locale] ?? $locale),
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
                        'label' => "{$label} - " . ($channelNames[$channelCode] ?? $channelCode) . " - " . ($localeNames[$locale] ?? $locale),
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
                    'label' => "{$label} - " . ($channelNames[$channelCode] ?? $channelCode),
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
                    'label' => "{$label} - " . ($localeNames[$locale] ?? $locale),
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
        <div class="flex gap-4 justify-between items-center mb-4 max-sm:flex-wrap">
            <div class="grid gap-1.5">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold leading-6">
                    @lang('admin::app.catalog.products.bulk-edit.action')
                </p>

                <p class="text-gray-600 dark:text-gray-300 text-sm">
                    @lang('admin::app.catalog.products.bulk-edit.description')
                </p>
            </div>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.catalog.products.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.account.edit.back-btn')
                </a>

                <button
                    class="primary-button"
                    @click="handleSave"
                >
                    @lang('admin::app.catalog.products.edit.types.configurable.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="h-[calc(100vh-170px)] mb-16 overflow-auto rounded-md shadow-sm border border-gray-200 dark:border-cherry-700 bg-white dark:bg-cherry-900 [--active-cell-color:rgb(var(--c-primary-600))]">
            <table class="table-fixed border border-gray-200 border-collapse w-full dark:border-cherry-700">
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
                />
            </table>
        </div>

        <x-admin::modal ref="imagePreviewModal" no-class="true">
            <x-slot:content>
                <v-image-viewer
                    v-if="previewMedia && previewMedia.isImage"
                    :src="previewMedia.url"
                    :file-name="previewMedia.fileName"
                    @close="closePreview"
                ></v-image-viewer>
            </x-slot>
        </x-admin::modal>

        <x-admin::modal ref="filePreviewModal" type="large">
            <x-slot:header>
                <p class="text-lg font-bold text-gray-800 dark:text-white" v-text="previewMedia ? previewMedia.fileName : ''"></p>
            </x-slot>

            <x-slot:content>
                <iframe
                    v-if="previewMedia && ! previewMedia.isImage"
                    :src="previewMedia.url"
                    class="w-full rounded"
                    style="height: 70vh;"
                ></iframe>
            </x-slot>
        </x-admin::modal>
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
                    allRows: this.initialData || [],
                    rowsPerPage: 100,
                    currentPage: 1,
                    isLoading: false,
                    updatedEntityData: {},
                    previewMedia: null,
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

                    this.$emitter.on('preview-image', (payload) => {
                        this.openPreview(payload);
                    });
                },

                openPreview(payload) {
                    const url = typeof payload === 'string' ? payload : payload?.url;

                    if (! url) {
                        return;
                    }

                    const fileName = (typeof payload === 'object' && payload?.fileName)
                        ? payload.fileName
                        : decodeURIComponent(url.split('/').pop());

                    this.previewMedia = {
                        url,
                        fileName,
                        isImage: /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i.test(fileName),
                    };

                    this.$nextTick(() => {
                        const modal = this.previewMedia.isImage ? this.$refs.imagePreviewModal : this.$refs.filePreviewModal;

                        modal?.open();
                    });
                },

                closePreview() {
                    this.$refs.imagePreviewModal?.close();
                    this.$refs.filePreviewModal?.close();

                    this.previewMedia = null;
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

                    this.warnOnDuplicateAxisTuple(entityId);
                },

                ownAxisTuple(row) {
                    const axes = row.axes || [];

                    if (! axes.length || ! row.parent_id) {
                        return null;
                    }

                    const edited = this.updatedEntityData[row.id] || {};
                    const own = row.values?.common || {};
                    const parts = [];

                    for (const code of axes) {
                        const value = code in edited ? edited[code] : own[code];

                        if (value === null || value === undefined || value === '') {
                            return null;
                        }

                        parts.push(code + '=' + String(value));
                    }

                    return row.parent_id + '::' + parts.join('|');
                },

                duplicateAxisSiblingOf(row) {
                    const tuple = this.ownAxisTuple(row);

                    if (! tuple) {
                        return null;
                    }

                    return this.allRows.find(
                        other => other.id !== row.id && this.ownAxisTuple(other) === tuple
                    ) ?? null;
                },

                warnOnDuplicateAxisTuple(entityId) {
                    const row = this.allRows.find(candidate => candidate.id === entityId);

                    if (! row) {
                        return;
                    }

                    if (! this.duplicateAxisSiblingOf(row)) {
                        return;
                    }

                    this.$emitter.emit('add-flash', {
                        type: 'warning',
                        message: @json(trans('admin::app.catalog.products.bulk-edit.validation.duplicate-axis')),
                    });
                },

                handleSave() {
                    if (Object.keys(this.updatedEntityData).length === 0) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: @json(trans('admin::app.catalog.products.bulk-edit.no-changes')),
                        });
                        return;
                    }

                    this.isLoading = true;

                    this.$axios.post(this.entitySaveUrl, {
                        data: this.updatedEntityData,
                    })
                    .then(response => {
                        this.updatedEntityData = {};

                        const message = response.data.message || @json(trans('admin::app.catalog.products.bulk-edit.success'));

                        document.addEventListener('unopim:navigate:success', () => {
                            window.app?.config?.globalProperties?.$emitter?.emit('add-flash', {
                                type: 'success',
                                message,
                            });
                        }, { once: true });

                        this.$navigate(response.data.redirect_url || "{{ route('admin.catalog.products.index') }}");
                    })
                    .catch(error => {
                        const payload = error.response?.data ?? {};
                        const details = Object.values(payload.errors ?? {}).flat();

                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: payload.message || @json(trans('admin::app.catalog.products.bulk-edit.validation.failed')),
                        });

                        details.slice(0, 5).forEach(detail => this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: detail,
                        }));
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
                },
            },
        });
    </script>

@endPushOnce
