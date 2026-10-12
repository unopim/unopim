@php
    $aclVersion = \Webkul\Admin\Http\Controllers\User\AclVersionController::fingerprint(auth()->guard('admin')->user());
@endphp

@pushOnce('scripts')
    <script type="module">
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
