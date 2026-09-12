<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.publications.versions.title')
    </x-slot>

    <x-admin::page-header
        :title="trans('passport::app.publications.versions.title')"
        :subtitle="trans('passport::app.publications.versions.subtitle', [
            'sku'     => $publication->product?->sku ?? $publication->uuid,
            'channel' => $publication->channel?->code ?? '',
        ])"
        :back="route('admin.catalog.passports.index')"
    />

    <div class="mt-4 bg-white dark:bg-cherry-900 rounded box-shadow overflow-x-auto"
         id="passport-versions"
         data-republish-url="{{ route('admin.catalog.passports.republish', $publication->id) }}">
        @if ($publication->versions->isEmpty())
            <p class="p-6 text-sm text-gray-500 dark:text-gray-400">
                @lang('passport::app.publications.versions.no-versions')
            </p>
        @else
            @php
                $canRepublish = bouncer()->hasPermission('catalog.passport.publish');

                $hasActions = $canRepublish && $publication->versions->contains(
                    fn ($version) => ! $version->is_current && $version->redacted_at === null
                );
            @endphp

            <x-admin::table>
                <x-admin::table.thead>
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('passport::app.publications.versions.locale')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.versions.version')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.releases.release')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.versions.published-at')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.versions.published-by')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.versions.status')</x-admin::table.th>
                        @if ($hasActions)
                            <x-admin::table.th class="text-right">@lang('passport::app.publications.versions.action')</x-admin::table.th>
                        @endif
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($publication->versions as $version)
                        <x-admin::table.tbody.tr class="border-t border-gray-100 dark:border-cherry-800">
                            <x-admin::table.td>{{ $version->locale?->code }}</x-admin::table.td>

                            <x-admin::table.td>
                                {{ $version->version }}

                                @if ($version->is_current)
                                    <span class="ml-2 text-xs font-semibold text-primary-700 dark:text-primary-300">
                                        @lang('passport::app.publications.versions.current-badge')
                                    </span>
                                @endif
                            </x-admin::table.td>

                            <x-admin::table.td>{{ $version->release?->sequence }}</x-admin::table.td>

                            <x-admin::table.td>{{ $version->published_at }}</x-admin::table.td>

                            <x-admin::table.td>
                                {{ $version->publishedBy?->name ?? trans('passport::app.publications.versions.system') }}
                            </x-admin::table.td>

                            <x-admin::table.td>
                                @if ($version->redacted_at !== null)
                                    @lang('passport::app.publications.versions.redacted')
                                @elseif ($version->is_current)
                                    @lang('passport::app.publications.versions.current')
                                @else
                                    @lang('passport::app.publications.versions.superseded')
                                @endif
                            </x-admin::table.td>

                            @if ($hasActions)
                                <x-admin::table.td class="text-right">
                                    @if (! $version->is_current && $version->redacted_at === null)
                                        <button type="button"
                                                class="passport-republish-btn text-primary-700 dark:text-primary-300 font-semibold"
                                                data-version-id="{{ $version->id }}">
                                            @lang('passport::app.publications.versions.republish')
                                        </button>
                                    @endif
                                </x-admin::table.td>
                            @endif
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </div>

    @php
        $canIssue = bouncer()->hasPermission('catalog.passport.publish') && $publication->status->isPubliclyResolvable();
    @endphp

    {{-- Releases: one number per publish moment across locales; the unit a printed carrier is bound to. --}}
    <div class="mt-4 bg-white dark:bg-cherry-900 rounded box-shadow overflow-x-auto">
        <h2 class="p-4 text-base font-semibold text-gray-800 dark:text-white">@lang('passport::app.publications.releases.title')</h2>

        @if ($publication->releases->isEmpty())
            <p class="px-4 pb-6 text-sm text-gray-500 dark:text-gray-400">@lang('passport::app.publications.releases.none')</p>
        @else
            <x-admin::table>
                <x-admin::table.thead>
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('passport::app.publications.releases.release')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.releases.published-at')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.releases.published-by')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.releases.locales')</x-admin::table.th>
                        @if ($canIssue)
                            <x-admin::table.th class="text-right">@lang('passport::app.publications.versions.action')</x-admin::table.th>
                        @endif
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($publication->releases as $release)
                        <x-admin::table.tbody.tr class="border-t border-gray-100 dark:border-cherry-800">
                            <x-admin::table.td>{{ $release->sequence }}</x-admin::table.td>
                            <x-admin::table.td>{{ $release->published_at }}</x-admin::table.td>
                            <x-admin::table.td>{{ $release->publishedBy?->name ?? trans('passport::app.publications.versions.system') }}</x-admin::table.td>
                            <x-admin::table.td>{{ $release->versions->map(fn ($version) => $version->locale?->code)->filter()->implode(', ') }}</x-admin::table.td>
                            @if ($canIssue)
                                <x-admin::table.td class="text-right">
                                    <form method="POST" action="{{ route('admin.catalog.passports.issue_carrier', [$publication->id, $release->sequence]) }}">
                                        @csrf
                                        <button type="submit" class="text-primary-700 dark:text-primary-300 font-semibold">
                                            @lang('passport::app.publications.carrier.issue')
                                        </button>
                                    </form>
                                </x-admin::table.td>
                            @endif
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </div>

    {{-- Issued carriers: the immutable record of what was printed and what it encodes. --}}
    <div class="mt-4 bg-white dark:bg-cherry-900 rounded box-shadow overflow-x-auto">
        <h2 class="p-4 text-base font-semibold text-gray-800 dark:text-white">@lang('passport::app.publications.carrier.title')</h2>

        @if ($publication->carrierIssuances->isEmpty())
            <p class="px-4 pb-6 text-sm text-gray-500 dark:text-gray-400">@lang('passport::app.publications.carrier.none')</p>
        @else
            <x-admin::table>
                <x-admin::table.thead>
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>@lang('passport::app.publications.carrier.issued-at')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.releases.release')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.carrier.target')</x-admin::table.th>
                        <x-admin::table.th>@lang('passport::app.publications.carrier.issued-by')</x-admin::table.th>
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @foreach ($publication->carrierIssuances as $issuance)
                        <x-admin::table.tbody.tr class="border-t border-gray-100 dark:border-cherry-800">
                            <x-admin::table.td>{{ $issuance->issued_at }}</x-admin::table.td>
                            <x-admin::table.td>{{ $issuance->release?->sequence }}</x-admin::table.td>
                            <x-admin::table.td><code class="text-xs">{{ $issuance->target }}</code></x-admin::table.td>
                            <x-admin::table.td>{{ $issuance->issuedBy?->name ?? trans('passport::app.publications.versions.system') }}</x-admin::table.td>
                        </x-admin::table.tbody.tr>
                    @endforeach
                </x-admin::table.tbody>
            </x-admin::table>
        @endif
    </div>

    @pushOnce('scripts')
    <script>
        (function () {
            var container = document.getElementById('passport-versions');

            if (! container) { return; }

            var republishUrl = container.dataset.republishUrl;
            var csrfToken    = '{{ csrf_token() }}';

            document.addEventListener('click', function (event) {
                var button = event.target.closest('.passport-republish-btn');

                if (! button || button.disabled) { return; }

                button.disabled = true;

                var original = button.textContent;
                button.textContent = @json(trans('passport::app.publications.versions.republishing'));

                fetch(republishUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ version_id: parseInt(button.dataset.versionId, 10) }),
                })
                .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
                .then(function (result) {
                    if (result.ok && result.data.redirect_url) {
                        window.location.href = result.data.redirect_url;

                        return;
                    }

                    button.textContent = result.data.message || original;
                    button.disabled = false;
                })
                .catch(function () {
                    button.textContent = original;
                    button.disabled = false;
                });
            });
        })();
    </script>
    @endPushOnce
</x-admin::layouts>
