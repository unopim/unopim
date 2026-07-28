@php
    $aclVersion = \Webkul\Admin\Http\Controllers\User\AclVersionController::fingerprint(auth()->guard('admin')->user());
@endphp

@pushOnce('scripts')
    <script type="module">
        /**
         * A page is rendered against the permissions the user held at that moment.
         * When an administrator edits the role afterwards, an already-open tab keeps
         * offering buttons the user no longer has — the server rejects the action,
         * but the stale UI still invites the click. Poll the fingerprint and reload
         * the tab once it moves.
         */
        (function () {
            if (window.__aclWatcherInstalled) {
                return;
            }

            window.__aclWatcherInstalled = true;

            const endpoint = "{{ route('admin.acl.version') }}";
            const booted = @json($aclVersion);

            if (! booted) {
                return;
            }

            const check = async () => {
                // Never reload out from under unsaved edits, and don't poll a
                // backgrounded tab — it re-checks as soon as it is looked at again.
                if (window.__unsavedBarCount > 0 || document.hidden) {
                    return;
                }

                try {
                    const response = await fetch(endpoint, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        return;
                    }

                    const { version } = await response.json();

                    if (version && version !== booted) {
                        window.location.reload();
                    }
                } catch (e) {
                    // Offline or a dropped request: leave the tab alone and retry later.
                }
            };

            setInterval(check, 60000);

            document.addEventListener('visibilitychange', () => {
                if (! document.hidden) {
                    check();
                }
            });
        })();
    </script>
@endPushOnce
