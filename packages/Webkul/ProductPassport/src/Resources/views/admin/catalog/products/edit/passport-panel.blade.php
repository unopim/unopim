@if ($passportChannel !== null && $passportEnabled)
<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow" id="passport-panel" data-product-id="{{ $product->id }}">
    <div class="flex justify-between items-center mb-2 gap-4">
        <p class="text-base text-gray-800 dark:text-white font-semibold">
            {{ trans('passport::app.catalog.products.edit.passport.title') }}
        </p>

        <button type="button"
                class="passport-publish-all-btn primary-button text-sm"
                data-locale-ids="{{ json_encode($passportRows->pluck('locale_id')->values()) }}">
            {{ trans('passport::app.catalog.products.edit.passport.publish-all') }}
        </button>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        @if ($passportAutoPublish)
            {{ trans('passport::app.catalog.products.edit.passport.auto-publish-on') }}
        @else
            {{ trans('passport::app.catalog.products.edit.passport.auto-publish-off') }}
        @endif
    </p>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <th class="py-2 font-medium">{{ trans('passport::app.catalog.products.edit.passport.locale') }}</th>
                <th class="py-2 font-medium">{{ trans('passport::app.catalog.products.edit.passport.version') }}</th>
                <th class="py-2 font-medium">{{ trans('passport::app.catalog.products.edit.passport.published-at') }}</th>
                <th class="py-2 font-medium">{{ trans('passport::app.catalog.products.edit.passport.missing-fields') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($passportRows as $row)
                <tr data-locale-code="{{ $row['locale_code'] }}" class="border-t border-gray-100 dark:border-cherry-800">
                    <td class="py-2">{{ $row['locale_code'] }}</td>
                    <td class="py-2">{{ $row['version'] ?? trans('passport::app.catalog.products.edit.passport.not-published') }}</td>
                    <td class="py-2">{{ $row['published_at'] ?? '—' }}</td>
                    <td class="py-2">{{ $row['missing_count'] ?? trans('passport::app.catalog.products.edit.passport.unscored') }}</td>
                    <td class="py-2 text-right">
                        @if (! empty($row['operator_link']))
                            <button type="button" class="passport-copy-link-btn text-violet-700 dark:text-violet-300 font-semibold mr-3"
                                    data-link="{{ $row['operator_link'] }}"
                                    data-label="{{ trans('passport::app.catalog.products.edit.passport.copy-operator-link') }}">
                                {{ trans('passport::app.catalog.products.edit.passport.copy-operator-link') }}
                            </button>
                        @endif

                        @if (! empty($row['authority_link']))
                            <button type="button" class="passport-copy-link-btn text-violet-700 dark:text-violet-300 font-semibold mr-3"
                                    data-link="{{ $row['authority_link'] }}"
                                    data-label="{{ trans('passport::app.catalog.products.edit.passport.copy-authority-link') }}">
                                {{ trans('passport::app.catalog.products.edit.passport.copy-authority-link') }}
                            </button>
                        @endif

                        @if (! empty($row['carrier_link']))
                            <a href="{{ $row['carrier_link'] }}" download
                               class="text-violet-700 dark:text-violet-300 font-semibold mr-3">
                                {{ trans('passport::app.catalog.products.edit.passport.download-qr') }}
                            </a>
                        @endif

                        <button type="button" class="passport-publish-btn text-violet-700 dark:text-violet-300 font-semibold"
                                data-locale-id="{{ $row['locale_id'] }}">
                            {{ $passportAutoPublish
                                ? trans('passport::app.catalog.products.edit.passport.republish')
                                : trans('passport::app.catalog.products.edit.passport.publish') }}
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@pushOnce('scripts')
<script>
    (function () {
        // Delegate on document, not #passport-panel: the panel is server-
        // rendered inside the Vue #app root, and Vue's mount detaches a
        // listener bound directly to the panel element.
        var publishRoute = '{{ route('admin.catalog.passports.publish', $product->id) }}';
        var channelId    = {{ $passportChannel->id }};
        var csrfToken    = '{{ csrf_token() }}';

        function publish(button, localeIds, doneLabel) {
            button.disabled = true;

            var original = button.textContent;
            button.textContent = @json(trans('passport::app.catalog.products.edit.passport.publishing'));

            fetch(publishRoute, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ channel_id: channelId, locale_ids: localeIds }),
            })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                button.textContent = result.ok ? doneLabel : (result.data.message || original);

                if (! result.ok) {
                    button.disabled = false;
                }
            })
            .catch(function () {
                button.textContent = original;
                button.disabled = false;
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

        document.addEventListener('click', function (event) {
            var copy = event.target.closest('.passport-copy-link-btn');

            if (copy) {
                copyLink(copy);

                return;
            }

            var single = event.target.closest('.passport-publish-btn');

            if (single && ! single.disabled) {
                publish(single, [parseInt(single.dataset.localeId, 10)], @json(trans('passport::app.catalog.products.edit.passport.queued')));

                return;
            }

            var all = event.target.closest('.passport-publish-all-btn');

            if (all && ! all.disabled) {
                var ids = JSON.parse(all.dataset.localeIds || '[]');

                if (ids.length) {
                    publish(all, ids, @json(trans('passport::app.catalog.products.edit.passport.queued')));
                }
            }
        });
    })();
</script>
@endPushOnce
@endif
