<x-admin::layouts>
    <x-slot:title>
        @lang('channel_connector::app.conflicts.show.title') #{{ $conflict->id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('channel_connector::app.conflicts.show.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.channel_connector.conflicts.index') }}" class="transparent-button">
                @lang('channel_connector::app.general.back')
            </a>
        </div>
    </div>

    {{-- Conflict Info Cards --}}
    <div class="mt-3.5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.product')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ $conflict->product?->sku ?? 'N/A' }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.connector')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ $conflict->connector?->name ?? 'N/A' }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.conflict-type')</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">
                @php
                    $typeKey = "channel_connector::app.conflicts.conflict-types.{$conflict->conflict_type}";
                    $typeLabel = trans($typeKey);
                @endphp
                {{ $typeLabel !== $typeKey ? $typeLabel : ucfirst(str_replace('_', ' ', $conflict->conflict_type)) }}
            </p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.resolution-status')</p>
            @php
                $statusColors = [
                    'unresolved'   => 'text-red-600 dark:text-red-400',
                    'pim_wins'     => 'text-green-600 dark:text-green-400',
                    'channel_wins' => 'text-green-600 dark:text-green-400',
                    'merged'       => 'text-green-600 dark:text-green-400',
                    'dismissed'    => 'text-gray-500 dark:text-gray-400',
                ];
                $statusColor = $statusColors[$conflict->resolution_status] ?? 'text-gray-800 dark:text-white';
                $resKey = "channel_connector::app.conflicts.resolution.{$conflict->resolution_status}";
                $resLabel = trans($resKey);
            @endphp
            <p class="text-lg font-semibold {{ $statusColor }}">
                {{ $resLabel !== $resKey ? $resLabel : ucfirst(str_replace('_', ' ', $conflict->resolution_status)) }}
            </p>
        </div>
    </div>

    {{-- Timestamps --}}
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.pim-modified-at')</p>
            <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $conflict->pim_modified_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.channel-modified-at')</p>
            <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $conflict->channel_modified_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
        </div>

        @if($conflict->resolved_by)
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.resolved-by')</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $conflict->resolvedBy?->name ?? 'N/A' }}</p>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('channel_connector::app.conflicts.fields.resolved-at')</p>
                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $conflict->resolved_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
            </div>
        @endif
    </div>

    {{-- Resolution Details (if resolved) --}}
    @if($conflict->resolution_details)
        <div class="mt-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('channel_connector::app.conflicts.resolution-details')
                </p>
                <pre class="overflow-auto rounded bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ json_encode($conflict->resolution_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif

    {{-- Side-by-Side Diff --}}
    @if($conflict->resolution_status === 'unresolved' && bouncer()->hasPermission('channel_connector.conflicts.edit'))
        <x-admin::form
            :action="route('admin.channel_connector.conflicts.resolve', $conflict->id)"
            method="PUT"
        >
            <div class="mt-4">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('channel_connector::app.conflicts.show.field-comparison')
                        </p>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="secondary-button text-sm"
                                onclick="selectAll('pim')"
                            >
                                @lang('channel_connector::app.conflicts.actions.pim-wins-all')
                            </button>

                            <button
                                type="button"
                                class="secondary-button text-sm"
                                onclick="selectAll('channel')"
                            >
                                @lang('channel_connector::app.conflicts.actions.channel-wins-all')
                            </button>
                        </div>
                    </div>

                    {{-- Locale Tabs --}}
                    @if(! empty($locales))
                        <div class="mb-4 border-b dark:border-gray-700">
                            <nav class="-mb-px flex gap-4" id="locale-tabs">
                                <button
                                    type="button"
                                    class="locale-tab border-b-2 border-blue-500 px-1 pb-2 text-sm font-medium text-blue-600 dark:text-blue-400"
                                    data-locale="common"
                                    onclick="switchLocaleTab('common', this)"
                                >
                                    @lang('channel_connector::app.conflicts.show.common')
                                </button>
                                @foreach($locales as $locale)
                                    <button
                                        type="button"
                                        class="locale-tab border-b-2 border-transparent px-1 pb-2 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                        data-locale="{{ $locale }}"
                                        onclick="switchLocaleTab('{{ $locale }}', this)"
                                    >
                                        {{ strtoupper($locale) }}
                                    </button>
                                @endforeach
                            </nav>
                        </div>
                    @endif

                    {{-- Common Fields Table --}}
                    <div class="locale-content" id="locale-content-common">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.field')</th>
                                    <th class="px-4 py-3 font-medium text-blue-600 dark:text-blue-400">@lang('channel_connector::app.conflicts.fields.pim-value')</th>
                                    <th class="px-4 py-3 font-medium text-orange-600 dark:text-orange-400">@lang('channel_connector::app.conflicts.fields.channel-value')</th>
                                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.winner')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($conflictingFields as $fieldCode => $fieldData)
                                    @if(empty($fieldData['is_locale_specific']))
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $fieldCode }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                <div class="rounded bg-blue-50 p-2 dark:bg-blue-900/20">
                                                    {{ is_array($fieldData['pim_value']) ? json_encode($fieldData['pim_value']) : ($fieldData['pim_value'] ?? '-') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                <div class="rounded bg-orange-50 p-2 dark:bg-orange-900/20">
                                                    {{ is_array($fieldData['channel_value']) ? json_encode($fieldData['channel_value']) : ($fieldData['channel_value'] ?? '-') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex gap-3">
                                                    <label class="flex items-center gap-1 text-sm">
                                                        <input
                                                            type="radio"
                                                            name="field_overrides[{{ $fieldCode }}]"
                                                            value="pim"
                                                            checked
                                                            class="field-override text-blue-600"
                                                        />
                                                        <span class="text-gray-700 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.pim')</span>
                                                    </label>
                                                    <label class="flex items-center gap-1 text-sm">
                                                        <input
                                                            type="radio"
                                                            name="field_overrides[{{ $fieldCode }}]"
                                                            value="channel"
                                                            class="field-override text-orange-600"
                                                        />
                                                        <span class="text-gray-700 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.channel')</span>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Locale-Specific Fields Tables --}}
                    @foreach($locales as $locale)
                        <div class="locale-content hidden" id="locale-content-{{ $locale }}">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.field')</th>
                                        <th class="px-4 py-3 font-medium text-blue-600 dark:text-blue-400">@lang('channel_connector::app.conflicts.fields.pim-value') ({{ strtoupper($locale) }})</th>
                                        <th class="px-4 py-3 font-medium text-orange-600 dark:text-orange-400">@lang('channel_connector::app.conflicts.fields.channel-value') ({{ strtoupper($locale) }})</th>
                                        <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.winner')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($conflictingFields as $fieldCode => $fieldData)
                                        @if(! empty($fieldData['locales'][$locale]))
                                            <tr class="border-b dark:border-gray-700">
                                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $fieldCode }}</td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                    <div class="rounded bg-blue-50 p-2 dark:bg-blue-900/20">
                                                        {{ is_array($fieldData['locales'][$locale]['pim_value']) ? json_encode($fieldData['locales'][$locale]['pim_value']) : ($fieldData['locales'][$locale]['pim_value'] ?? '-') }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                    <div class="rounded bg-orange-50 p-2 dark:bg-orange-900/20">
                                                        {{ is_array($fieldData['locales'][$locale]['channel_value']) ? json_encode($fieldData['locales'][$locale]['channel_value']) : ($fieldData['locales'][$locale]['channel_value'] ?? '-') }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex gap-3">
                                                        <label class="flex items-center gap-1 text-sm">
                                                            <input
                                                                type="radio"
                                                                name="field_overrides[{{ $fieldCode }}]"
                                                                value="pim"
                                                                checked
                                                                class="field-override text-blue-600"
                                                            />
                                                            <span class="text-gray-700 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.pim')</span>
                                                        </label>
                                                        <label class="flex items-center gap-1 text-sm">
                                                            <input
                                                                type="radio"
                                                                name="field_overrides[{{ $fieldCode }}]"
                                                                value="channel"
                                                                class="field-override text-orange-600"
                                                            />
                                                            <span class="text-gray-700 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.channel')</span>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    {{-- Resolution Actions --}}
                    <div class="mt-6 flex items-center gap-3 border-t pt-4 dark:border-gray-700">
                        <button
                            type="submit"
                            name="resolution"
                            value="merged"
                            class="primary-button"
                        >
                            @lang('channel_connector::app.conflicts.actions.resolve')
                        </button>

                        <button
                            type="submit"
                            name="resolution"
                            value="pim_wins"
                            class="secondary-button"
                        >
                            @lang('channel_connector::app.conflicts.actions.pim-wins-all')
                        </button>

                        <button
                            type="submit"
                            name="resolution"
                            value="channel_wins"
                            class="secondary-button"
                        >
                            @lang('channel_connector::app.conflicts.actions.channel-wins-all')
                        </button>

                        <button
                            type="submit"
                            name="resolution"
                            value="dismissed"
                            class="transparent-button"
                        >
                            @lang('channel_connector::app.conflicts.actions.dismiss')
                        </button>
                    </div>
                </div>
            </div>
        </x-admin::form>
    @elseif(! empty($conflictingFields))
        {{-- Read-only view for already resolved conflicts --}}
        <div class="mt-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('channel_connector::app.conflicts.show.field-comparison')
                </p>

                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.field')</th>
                            <th class="px-4 py-3 font-medium text-blue-600 dark:text-blue-400">@lang('channel_connector::app.conflicts.fields.pim-value')</th>
                            <th class="px-4 py-3 font-medium text-orange-600 dark:text-orange-400">@lang('channel_connector::app.conflicts.fields.channel-value')</th>
                            <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-300">@lang('channel_connector::app.conflicts.show.locale')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conflictingFields as $fieldCode => $fieldData)
                            @if(empty($fieldData['is_locale_specific']))
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $fieldCode }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ is_array($fieldData['pim_value']) ? json_encode($fieldData['pim_value']) : ($fieldData['pim_value'] ?? '-') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ is_array($fieldData['channel_value']) ? json_encode($fieldData['channel_value']) : ($fieldData['channel_value'] ?? '-') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">-</td>
                                </tr>
                            @endif

                            @if(! empty($fieldData['locales']))
                                @foreach($fieldData['locales'] as $locale => $localeValues)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $fieldCode }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ is_array($localeValues['pim_value']) ? json_encode($localeValues['pim_value']) : ($localeValues['pim_value'] ?? '-') }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ is_array($localeValues['channel_value']) ? json_encode($localeValues['channel_value']) : ($localeValues['channel_value'] ?? '-') }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ strtoupper($locale) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @pushOnce('scripts')
        <script type="module">
            window.switchLocaleTab = function(locale, el) {
                document.querySelectorAll('.locale-content').forEach(c => c.classList.add('hidden'));
                document.querySelectorAll('.locale-tab').forEach(t => {
                    t.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                    t.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                });

                const content = document.getElementById('locale-content-' + locale);
                if (content) {
                    content.classList.remove('hidden');
                }

                el.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                el.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            };

            window.selectAll = function(winner) {
                document.querySelectorAll('.field-override').forEach(radio => {
                    if (radio.value === winner) {
                        radio.checked = true;
                    }
                });
            };
        </script>
    @endPushOnce
</x-admin::layouts>
