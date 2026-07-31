@if ($passportChannel !== null && $passportEnabled)
<x-admin::product.section-drawer
    id="passport"
    :title="trans('passport::app.catalog.products.edit.passport.title')"
    :subtitle="trans('passport::app.catalog.products.edit.passport.subtitle')"
    icon="icon-file"
>
    <x-slot:toggle>
        <x-admin::product.section-card
            id="passport"
            :title="trans('passport::app.catalog.products.edit.passport.title')"
            icon="icon-file"
            :summary="trans('passport::app.catalog.products.edit.passport.published-summary', [
                'published' => $passportPublishedCount,
                'total'     => $passportRows->count(),
            ])"
        />
    </x-slot:toggle>

    <x-slot:headerActions>
        @if ($passportCanPublish)
            <button
                type="button"
                class="passport-publish-all-btn primary-button shrink-0"
                data-locale-ids="{{ json_encode($passportRows->where('ready', true)->pluck('locale_id')->values()) }}"
                title="{{ $passportOffline
                    ? trans('passport::app.publications.publish-withdrawn')
                    : ($passportRows->every('ready')
                        ? trans('passport::app.catalog.products.edit.passport.publish-all')
                        : trans('passport::app.catalog.products.edit.passport.publish-blocked')) }}"
                @disabled($passportOffline || ! $passportRows->every('ready'))
            >
                {{ trans('passport::app.catalog.products.edit.passport.publish-all') }}
            </button>
        @endif
    </x-slot:headerActions>

    <x-slot:content>
        <div
            id="passport-panel"
            data-product-id="{{ $product->id }}"
            data-republish-url="{{ $passportRepublishUrl }}"
            class="grid gap-2.5"
        >
            @if ($passportOffline)
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow flex items-center gap-2.5">
                    <x-admin::badge variant="danger">
                        {{ $passportStatusLabel }}
                    </x-admin::badge>

                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        @lang('passport::app.publications.publish-withdrawn')
                    </p>
                </div>
            @endif

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow flex flex-wrap justify-between items-center gap-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if ($passportAutoPublish)
                        {{ trans('passport::app.catalog.products.edit.passport.auto-publish-on') }}
                    @else
                        {{ trans('passport::app.catalog.products.edit.passport.auto-publish-off') }}
                    @endif
                </p>

                <p
                    id="passport-total-views"
                    class="text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap"
                >
                    {{ trans('passport::app.catalog.products.edit.passport.total-views', ['count' => $passportViews]) }}
                </p>
            </div>

            <div class="bg-white dark:bg-cherry-900 rounded box-shadow">
                <x-admin::tabs position="left">
                    <x-admin::tabs.item
                        :title="trans('passport::app.catalog.products.edit.passport.tabs.locales')"
                        value="passport-locales"
                        :is-selected="true"
                    >
                        <div class="passport-locales-table overflow-x-auto">
                            <x-admin::table class="min-w-0">
                                <x-admin::table.thead>
                                    <x-admin::table.thead.tr>
                                        <x-admin::table.th>{{ trans('passport::app.catalog.products.edit.passport.locale') }}</x-admin::table.th>
                                        <x-admin::table.th>{{ trans('passport::app.catalog.products.edit.passport.version') }}</x-admin::table.th>
                                        <x-admin::table.th>{{ trans('passport::app.catalog.products.edit.passport.published-at') }}</x-admin::table.th>
                                        <x-admin::table.th>{{ trans('passport::app.catalog.products.edit.passport.missing-fields') }}</x-admin::table.th>
                                        <x-admin::table.th class="text-right">{{ trans('passport::app.catalog.products.edit.passport.actions') }}</x-admin::table.th>
                                    </x-admin::table.thead.tr>
                                </x-admin::table.thead>

                                <x-admin::table.tbody>
                                    @foreach ($passportRows as $row)
                                        <x-admin::table.tbody.tr
                                            data-locale-code="{{ $row['locale_code'] }}"
                                            class="border-t border-gray-200 dark:border-gray-800"
                                        >
                                            <x-admin::table.td>{{ $row['locale_code'] }}</x-admin::table.td>

                                            <x-admin::table.td data-passport-version="{{ $row['locale_id'] }}">{{ $row['version'] ?? trans('passport::app.catalog.products.edit.passport.not-published') }}</x-admin::table.td>

                                            <x-admin::table.td data-passport-published-at="{{ $row['locale_id'] }}">{{ $row['published_at'] ?? '—' }}</x-admin::table.td>

                                            <x-admin::table.td>
                                                @if ($row['missing_count'] === null)
                                                    @if ($row['template_url'])
                                                        <a
                                                            href="{{ $row['template_url'] }}"
                                                            title="{{ trans('passport::app.templates.builder.edit') }}"
                                                        >
                                                            <x-admin::badge variant="danger">
                                                                @lang('passport::app.catalog.products.edit.passport.missing-template')
                                                            </x-admin::badge>
                                                        </a>
                                                    @else
                                                        <x-admin::badge variant="danger">
                                                            @lang('passport::app.catalog.products.edit.passport.missing-template')
                                                        </x-admin::badge>
                                                    @endif
                                                @elseif ($row['missing_count'] > 0)
                                                    <x-admin::dropdown
                                                        position="bottom-right"
                                                        :teleport="true"
                                                    >
                                                        <x-slot:toggle>
                                                            <button
                                                                type="button"
                                                                aria-label="{{ trans('passport::app.catalog.products.edit.passport.missing-fields') }}"
                                                            >
                                                                <x-admin::badge variant="danger">
                                                                    {{ $row['missing_count'] }}
                                                                </x-admin::badge>
                                                            </button>
                                                        </x-slot>

                                                        <x-slot:content class="passport-missing-fields w-80 max-h-80 overflow-y-auto">
                                                            <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                                                                @lang('passport::app.catalog.products.edit.passport.publish-blocked')
                                                            </p>

                                                            <div class="grid gap-2">
                                                                @foreach ($row['missing_fields'] as $missingField)
                                                                    <div class="flex items-center justify-between gap-4">
                                                                        @if ($missingField['action_url'])
                                                                            <a
                                                                                href="{{ $missingField['action_url'] }}"
                                                                                class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-300"
                                                                                title="{{ trans('passport::app.templates.builder.edit') }}"
                                                                            >
                                                                                {{ $missingField['label'] }}
                                                                            </a>
                                                                        @else
                                                                            <span class="font-medium text-gray-800 dark:text-white">
                                                                                {{ $missingField['label'] }}
                                                                            </span>
                                                                        @endif

                                                                        <x-admin::badge variant="neutral">
                                                                            {{ $missingField['source'] }}
                                                                        </x-admin::badge>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </x-slot>
                                                    </x-admin::dropdown>
                                                @else
                                                    <x-admin::badge variant="success">0</x-admin::badge>
                                                @endif
                                            </x-admin::table.td>

                                            <x-admin::table.td>
                                                <div class="flex items-center justify-end gap-2.5">
                                                    @if (! empty($row['preview_url']))
                                                        <a
                                                            href="{{ $row['preview_url'] }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="secondary-button"
                                                        >
                                                            @lang('passport::app.catalog.products.edit.passport.preview')
                                                        </a>
                                                    @endif

                                                    @if ($passportCanPublish)
                                                        <button
                                                            type="button"
                                                            class="passport-publish-btn primary-button"
                                                            data-locale-id="{{ $row['locale_id'] }}"
                                                            title="{{ $passportOffline
                                                                ? trans('passport::app.publications.publish-withdrawn')
                                                                : ($row['ready']
                                                                    ? trans('passport::app.catalog.products.edit.passport.publish')
                                                                    : trans('passport::app.catalog.products.edit.passport.publish-blocked')) }}"
                                                            @disabled($passportOffline || ! $row['ready'])
                                                        >
                                                            {{ $row['version'] === null
                                                                ? trans('passport::app.catalog.products.edit.passport.publish')
                                                                : trans('passport::app.catalog.products.edit.passport.republish') }}
                                                        </button>
                                                    @endif

                                                    @if (! empty($row['operator_link']) || ! empty($row['authority_link']) || ! empty($row['carrier_link']))
                                                        <x-admin::dropdown
                                                            position="bottom-{{ core()->getRequestedLocale()?->direction === 'rtl' ? 'left' : 'right' }}"
                                                            :teleport="true"
                                                        >
                                                            <x-slot:toggle>
                                                                <button
                                                                    type="button"
                                                                    class="icon-chevron-down cursor-pointer rounded-md text-2xl leading-none text-gray-500 transition-all hover:text-primary-600 dark:text-gray-400 dark:hover:text-white"
                                                                    aria-label="{{ trans('passport::app.catalog.products.edit.passport.actions') }}"
                                                                ></button>
                                                            </x-slot>

                                                            <x-slot:menu>
                                                                @if (! empty($row['operator_link']))
                                                                    <x-admin::dropdown.menu.item
                                                                        class="passport-copy-link-btn"
                                                                        data-link="{{ $row['operator_link'] }}"
                                                                        data-label="{{ trans('passport::app.catalog.products.edit.passport.copy-operator-link') }}"
                                                                    >
                                                                        @lang('passport::app.catalog.products.edit.passport.copy-operator-link')
                                                                    </x-admin::dropdown.menu.item>
                                                                @endif

                                                                @if (! empty($row['authority_link']))
                                                                    <x-admin::dropdown.menu.item
                                                                        class="passport-copy-link-btn"
                                                                        data-link="{{ $row['authority_link'] }}"
                                                                        data-label="{{ trans('passport::app.catalog.products.edit.passport.copy-authority-link') }}"
                                                                    >
                                                                        @lang('passport::app.catalog.products.edit.passport.copy-authority-link')
                                                                    </x-admin::dropdown.menu.item>
                                                                @endif

                                                                @if (! empty($row['carrier_link']))
                                                                    <x-admin::dropdown.menu.item>
                                                                        <a
                                                                            href="{{ $row['carrier_link'] }}"
                                                                            download
                                                                            class="block"
                                                                        >
                                                                            @lang('passport::app.catalog.products.edit.passport.download-qr')
                                                                        </a>
                                                                    </x-admin::dropdown.menu.item>
                                                                @endif
                                                            </x-slot>
                                                        </x-admin::dropdown>
                                                    @endif
                                                </div>
                                            </x-admin::table.td>
                                        </x-admin::table.tbody.tr>
                                    @endforeach
                                </x-admin::table.tbody>
                            </x-admin::table>
                        </div>
                    </x-admin::tabs.item>

                    <x-admin::tabs.item
                        :title="trans('passport::app.catalog.products.edit.passport.tabs.history')"
                        value="passport-history"
                    >
                        @if ($passportVersions->isEmpty())
                            <p class="py-6 text-sm text-gray-500 dark:text-gray-400 text-center">
                                @lang('passport::app.publications.versions.no-versions')
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <x-admin::table>
                                    <x-admin::table.thead>
                                        <x-admin::table.thead.tr>
                                            <x-admin::table.th>@lang('passport::app.publications.versions.locale')</x-admin::table.th>
                                            <x-admin::table.th>@lang('passport::app.publications.versions.version')</x-admin::table.th>
                                            <x-admin::table.th>@lang('passport::app.publications.versions.published-at')</x-admin::table.th>
                                            <x-admin::table.th>@lang('passport::app.publications.versions.published-by')</x-admin::table.th>
                                            <x-admin::table.th>@lang('passport::app.publications.versions.status')</x-admin::table.th>
                                            <x-admin::table.th class="text-right">@lang('passport::app.publications.versions.action')</x-admin::table.th>
                                        </x-admin::table.thead.tr>
                                    </x-admin::table.thead>

                                    <x-admin::table.tbody>
                                        @foreach ($passportVersions as $version)
                                            <x-admin::table.tbody.tr class="border-t border-gray-200 dark:border-gray-800">
                                                <x-admin::table.td>{{ $version->locale?->code }}</x-admin::table.td>

                                                <x-admin::table.td>
                                                    {{ $version->version }}

                                                    @if ($version->is_current)
                                                        <span class="ltr:ml-2 rtl:mr-2 text-xs font-semibold text-primary-600 dark:text-primary-400">
                                                            @lang('passport::app.publications.versions.current-badge')
                                                        </span>
                                                    @endif
                                                </x-admin::table.td>

                                                <x-admin::table.td>{{ $version->published_at }}</x-admin::table.td>

                                                <x-admin::table.td>{{ $version->publishedBy?->name ?? trans('passport::app.publications.versions.system') }}</x-admin::table.td>

                                                <x-admin::table.td>
                                                    @if ($version->redacted_at !== null)
                                                        @lang('passport::app.publications.versions.redacted')
                                                    @elseif ($version->is_current)
                                                        @lang('passport::app.publications.versions.current')
                                                    @else
                                                        @lang('passport::app.publications.versions.superseded')
                                                    @endif
                                                </x-admin::table.td>

                                                <x-admin::table.td>
                                                    <div class="flex items-center justify-end">
                                                        @if (! $version->is_current && $version->redacted_at === null && $passportCanPublish)
                                                            <button
                                                                type="button"
                                                                class="passport-republish-btn secondary-button"
                                                                data-version-id="{{ $version->id }}"
                                                            >
                                                                @lang('passport::app.publications.versions.republish')
                                                            </button>
                                                        @endif
                                                    </div>
                                                </x-admin::table.td>
                                            </x-admin::table.tbody.tr>
                                        @endforeach
                                    </x-admin::table.tbody>
                                </x-admin::table>
                            </div>

                            @if ($passportHistoryUrl !== null && $passportHistoryTotal > $passportVersions->count())
                                <div class="pt-4 text-right">
                                    <a
                                        href="{{ $passportHistoryUrl }}"
                                        class="text-sm font-medium text-primary-600 dark:text-primary-400"
                                    >
                                        {{ trans('passport::app.catalog.products.edit.passport.view-all-versions', ['count' => $passportHistoryTotal]) }}
                                    </a>
                                </div>
                            @endif
                        @endif
                    </x-admin::tabs.item>
                </x-admin::tabs>
            </div>
        </div>
    </x-slot:content>
</x-admin::product.section-drawer>

@pushOnce('scripts')
<script>
    (function () {
        {{-- Delegate on document, not #passport-panel: Vue's mount detaches a listener bound directly to the panel element. --}}
        var publishRoute = '{{ route('admin.catalog.passports.publish', $product->id) }}';
        var channelId    = {{ $passportChannel->id }};
        var csrfToken    = '{{ csrf_token() }}';

        var pollInterval = 2000;
        var pollLimit    = 45;

        function flash(type, message) {
            window.app.config.globalProperties.$emitter?.emit('add-flash', { type: type, message: message });
        }

        function applyLocaleState(locales) {
            locales.forEach(function (locale) {
                var version = document.querySelector('[data-passport-version="' + locale.locale_id + '"]');
                var publishedAt = document.querySelector('[data-passport-published-at="' + locale.locale_id + '"]');

                if (version && locale.version) {
                    version.textContent = locale.version;
                }

                if (publishedAt && locale.published_at) {
                    publishedAt.textContent = locale.published_at;
                }

                var publishButton = document.querySelector('.passport-publish-btn[data-locale-id="' + locale.locale_id + '"]');

                if (publishButton && locale.version) {
                    publishButton.textContent = @json(trans('passport::app.catalog.products.edit.passport.republish'));
                }
            });
        }

        {{-- Publishing runs on the queue, so the panel follows the attempt the request created rather than claiming a result the worker has not reached yet. --}}
        function watch(url, button, original, attempt) {
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (! data.settled) {
                    if (attempt >= pollLimit) {
                        button.textContent = original;
                        button.disabled = false;

                        flash('warning', @json(trans('passport::app.catalog.products.edit.passport.publish-still-running')));

                        return;
                    }

                    setTimeout(function () { watch(url, button, original, attempt + 1); }, pollInterval);

                    return;
                }

                button.disabled = false;
                button.textContent = original;

                applyLocaleState(data.locales);

                if (data.status === 'failed') {
                    flash('error', @json(trans('passport::app.catalog.products.edit.passport.publish-failed')));

                    return;
                }

                if (data.refused) {
                    flash('error', @json(trans('passport::app.publications.publish-withdrawn')));

                    return;
                }

                var published = data.locales.filter(function (locale) { return locale.published; }).length;

                flash(
                    published ? 'success' : 'info',
                    published
                        ? @json(trans('passport::app.catalog.products.edit.passport.published-now')).replace(':count', published)
                        : @json(trans('passport::app.catalog.products.edit.passport.publish-unchanged'))
                );
            })
            .catch(function () {
                button.textContent = original;
                button.disabled = false;

                flash('error', @json(trans('admin::app.components.form.ajax-error')));
            });
        }

        function publish(button, localeIds) {
            button.disabled = true;

            var original = button.textContent;
            button.textContent = @json(trans('passport::app.catalog.products.edit.passport.publishing'));

            fetch(publishRoute, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ channel_id: channelId, locale_ids: localeIds }),
            })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (! result.ok) {
                    button.textContent = original;
                    button.disabled = false;

                    flash('error', result.data.message || @json(trans('admin::app.components.form.ajax-error')));

                    return;
                }

                watch(result.data.attempt_url, button, original, 1);
            })
            .catch(function () {
                button.textContent = original;
                button.disabled = false;

                flash('error', @json(trans('admin::app.components.form.ajax-error')));
            });
        }

        function copyLink(button) {
            var link = button.dataset.link;
            var restore = button.dataset.label;

            function flash() {
                button.textContent = @json(trans('passport::app.catalog.products.edit.passport.link-copied'));
                setTimeout(function () { button.textContent = restore; }, 1500);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(flash).catch(flash);

                return;
            }

            var field = document.createElement('textarea');
            field.value = link;
            document.body.appendChild(field);
            field.select();
            try { document.execCommand('copy'); } catch (error) { /* clipboard unavailable */ }
            document.body.removeChild(field);
            flash();
        }

        function republish(button) {
            var panel = document.getElementById('passport-panel');
            var url = panel && panel.dataset.republishUrl;

            if (! url) { return; }

            button.disabled = true;

            var original = button.textContent;
            button.textContent = @json(trans('passport::app.publications.versions.republishing'));

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ version_id: parseInt(button.dataset.versionId, 10) }),
            })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                button.textContent = result.ok
                    ? @json(trans('passport::app.catalog.products.edit.passport.queued'))
                    : (result.data.message || original);

                if (! result.ok) {
                    button.disabled = false;
                }
            })
            .catch(function () {
                button.textContent = original;
                button.disabled = false;
            });
        }

        document.addEventListener('click', function (event) {
            var rollback = event.target.closest('.passport-republish-btn');

            if (rollback && ! rollback.disabled) {
                republish(rollback);

                return;
            }

            var copy = event.target.closest('.passport-copy-link-btn');

            if (copy) {
                copyLink(copy);

                return;
            }

            var single = event.target.closest('.passport-publish-btn');

            if (single && ! single.disabled) {
                publish(single, [parseInt(single.dataset.localeId, 10)]);

                return;
            }

            var all = event.target.closest('.passport-publish-all-btn');

            if (all && ! all.disabled) {
                var ids = JSON.parse(all.dataset.localeIds || '[]');

                if (ids.length) {
                    publish(all, ids);
                }
            }
        });
    })();
</script>
@endPushOnce
@endif
