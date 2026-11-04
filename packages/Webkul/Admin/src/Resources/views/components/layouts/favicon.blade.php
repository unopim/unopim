@php
    $favicon = core()->getConfigData('general.design.admin_logo.favicon');

    $darkFavicon = core()->getConfigData('general.design.admin_logo.favicon_dark');

    $lightFaviconUrl = $favicon ? Storage::url($favicon) : unopim_asset('images/favicon.svg');

    $darkFaviconUrl = $darkFavicon ? Storage::url($darkFavicon) : null;

    $prefersDark = in_array(request()->cookie('dark_mode'), ['1', 'dark'], true);
@endphp

<link
    type="image/x-icon"
    href="{{ $darkFaviconUrl && $prefersDark ? $darkFaviconUrl : $lightFaviconUrl }}"
    rel="shortcut icon"
    sizes="16x16"
    @if ($darkFaviconUrl)
        id="admin-favicon"
        data-light="{{ $lightFaviconUrl }}"
        data-dark="{{ $darkFaviconUrl }}"
    @endif
/>

@if ($darkFaviconUrl)
    <script>
        (() => {
            const favicon = document.getElementById('admin-favicon');

            const sync = () => {
                const href = document.documentElement.classList.contains('dark') ? favicon.dataset.dark : favicon.dataset.light;

                if (favicon.getAttribute('href') !== href) {
                    favicon.setAttribute('href', href);
                }
            };

            sync();

            new MutationObserver(sync).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>
@endif
