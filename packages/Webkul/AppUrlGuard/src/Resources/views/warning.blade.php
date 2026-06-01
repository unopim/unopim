{{--
    APP_URL mismatch warning — UnoPim-styled centered modal, fully self-contained.

    Rendered by Webkul\AppUrlGuard\Http\Middleware\VerifyAppUrlMatches and
    injected before </body> in debug mode only.

    IMPORTANT: this modal must render correctly even when the page's own assets
    fail to load (that is the very problem it warns about). So it uses scoped CSS
    (purge-proof) and INLINE SVG icons — never the icomoon icon font, which 404s
    on a mismatched APP_URL.

    Props: $configured (APP_URL in .env), $actual (host the browser is on),
           $justLoggedIn (reset the step state right after login).
--}}
<div id="unopim-appurl-backdrop" class="unopim-appurl-backdrop" data-just-logged-in="{{ $justLoggedIn ? 'true' : 'false' }}" data-check-url="{{ $checkUrl }}">
    <div id="unopim-appurl-warning" class="unopim-appurl-card" role="alertdialog" aria-modal="true">
        <div class="unopim-appurl-header">
            <svg class="unopim-appurl-header-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
            </svg>

            <strong class="unopim-appurl-title">@lang('APP_URL Mismatch Detected')</strong>

            <div class="unopim-appurl-actions">
                <button type="button" class="unopim-appurl-icon-btn" title="@lang('Dismiss')" aria-label="@lang('Dismiss')" onclick="unopimAppurlDismiss(event)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="unopim-appurl-body">
            <p class="unopim-appurl-desc">
                @lang('Your frontend assets (CSS, JS) are pinned to the configured APP_URL. Update it to match the host you are using, otherwise the styles and scripts will not load.')
            </p>

            <div class="unopim-appurl-grid">
                <div class="unopim-appurl-compare unopim-appurl-compare--error">
                    <span class="unopim-appurl-compare-label">@lang('Configured (.env)')</span>
                    <span class="unopim-appurl-compare-value">{{ $configured }}</span>
                </div>

                <div class="unopim-appurl-compare unopim-appurl-compare--success">
                    <span class="unopim-appurl-compare-label">@lang('Actual (Browser)')</span>
                    <span class="unopim-appurl-compare-value">{{ $actual }}</span>
                </div>
            </div>

            <div class="unopim-appurl-steps">
                <label class="unopim-appurl-step">
                    <input type="checkbox" id="unopim-appurl-step-1" class="unopim-appurl-check-input" onchange="unopimAppurlStep(1)">
                    <span class="unopim-appurl-check"></span>

                    <span class="unopim-appurl-step-body">
                        <span class="unopim-appurl-step-text">@lang('Update :key in your .env file:', ['key' => 'APP_URL'])</span>

                        <span class="unopim-appurl-code">
                            <span class="unopim-appurl-code-text">APP_URL={{ $actual }}</span>

                            <button type="button" class="unopim-appurl-copy" data-copy="APP_URL={{ $actual }}" onclick="unopimAppurlCopy(this, event)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <rect x="9" y="9" width="11" height="11" rx="2" />
                                    <path d="M5 15V5a2 2 0 012-2h10" />
                                </svg>
                                <span class="unopim-appurl-copy-label">@lang('Copy')</span>
                            </button>
                        </span>
                    </span>
                </label>

                <label class="unopim-appurl-step">
                    <input type="checkbox" id="unopim-appurl-step-2" class="unopim-appurl-check-input" onchange="unopimAppurlStep(2)">
                    <span class="unopim-appurl-check"></span>

                    <span class="unopim-appurl-step-body">
                        <span class="unopim-appurl-step-text">@lang('Clear the application cache:')</span>

                        <span class="unopim-appurl-code">
                            <span class="unopim-appurl-code-text">php artisan optimize:clear</span>

                            <button type="button" class="unopim-appurl-copy" data-copy="php artisan optimize:clear" onclick="unopimAppurlCopy(this, event)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <rect x="9" y="9" width="11" height="11" rx="2" />
                                    <path d="M5 15V5a2 2 0 012-2h10" />
                                </svg>
                                <span class="unopim-appurl-copy-label">@lang('Copy')</span>
                            </button>
                        </span>
                    </span>
                </label>

                <label class="unopim-appurl-step">
                    <input type="checkbox" id="unopim-appurl-step-3" class="unopim-appurl-check-input" onchange="unopimAppurlStep(3)">
                    <span class="unopim-appurl-check"></span>

                    <span class="unopim-appurl-step-body">
                        <span class="unopim-appurl-step-text">@lang('Hard refresh the page:')</span>
                        <span class="unopim-appurl-kbds">
                            <kbd class="unopim-appurl-kbd">Ctrl</kbd> + <kbd class="unopim-appurl-kbd">Shift</kbd> + <kbd class="unopim-appurl-kbd">R</kbd>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>

@verbatim
<style>
    #unopim-appurl-backdrop {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        box-sizing: border-box;
    }

    #unopim-appurl-backdrop * {
        box-sizing: border-box;
    }

    /* Full-screen dim backdrop that hides the page behind the modal. */
    .unopim-appurl-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        padding: 16px;
        background: rgba(15, 23, 42, 0.55);
        -webkit-backdrop-filter: blur(3px);
        backdrop-filter: blur(3px);
        align-items: center;
        justify-content: center;
        overflow: auto;
    }

    .unopim-appurl-backdrop.is-open {
        display: flex;
    }

    .unopim-appurl-card {
        position: relative;
        width: 440px;
        max-width: 100%;
        max-height: calc(100vh - 32px);
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.35);
        animation: unopimAppurlIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    html.dark .unopim-appurl-card {
        background: #26283D;
        border-color: #28273F;
    }

    @keyframes unopimAppurlIn {
        from { opacity: 0; transform: translateY(-12px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .unopim-appurl-header {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        padding: 13px 14px 13px 16px;
        border-bottom: 1px solid #eef0f3;
    }

    html.dark .unopim-appurl-header {
        border-bottom-color: #28273F;
    }

    .unopim-appurl-header-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        color: #f59e0b;
    }

    .unopim-appurl-title {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
    }

    html.dark .unopim-appurl-title {
        color: #f8fafc;
    }

    .unopim-appurl-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    /* Visible button chip so it shows even if no page CSS loads. */
    .unopim-appurl-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        cursor: pointer;
        color: #475569;
        transition: all 0.15s ease;
    }

    .unopim-appurl-icon-btn svg {
        width: 15px;
        height: 15px;
    }

    .unopim-appurl-icon-btn:hover {
        background: #ede9fe;
        border-color: #ddd6fe;
        color: #6d28d9;
    }

    html.dark .unopim-appurl-icon-btn {
        background: #1F1C30;
        border-color: #353061;
        color: #cbd5e1;
    }

    .unopim-appurl-body {
        padding: 16px;
        overflow: auto;
    }

    .unopim-appurl-desc {
        margin: 0 0 14px 0;
        font-size: 13px;
        line-height: 1.5;
        color: #4b5563;
    }

    html.dark .unopim-appurl-desc {
        color: #cbd5e1;
    }

    .unopim-appurl-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }

    .unopim-appurl-compare {
        padding: 9px 11px;
        border-radius: 8px;
        border: 1px solid;
        min-width: 0;
    }

    .unopim-appurl-compare--error {
        background: #fef2f2;
        border-color: #fee2e2;
    }

    .unopim-appurl-compare--success {
        background: #f0fdf4;
        border-color: #dcfce7;
    }

    html.dark .unopim-appurl-compare--error {
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.25);
    }

    html.dark .unopim-appurl-compare--success {
        background: rgba(34, 197, 94, 0.08);
        border-color: rgba(34, 197, 94, 0.25);
    }

    .unopim-appurl-compare-label {
        display: block;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .unopim-appurl-compare--error .unopim-appurl-compare-label { color: #b91c1c; }
    .unopim-appurl-compare--success .unopim-appurl-compare-label { color: #15803d; }
    html.dark .unopim-appurl-compare--error .unopim-appurl-compare-label { color: #fca5a5; }
    html.dark .unopim-appurl-compare--success .unopim-appurl-compare-label { color: #86efac; }

    .unopim-appurl-compare-value {
        display: block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11.5px;
        font-weight: 500;
        word-break: break-all;
        color: #1f2937;
    }

    html.dark .unopim-appurl-compare-value {
        color: #e2e8f0;
    }

    .unopim-appurl-steps {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .unopim-appurl-step {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        cursor: pointer;
        user-select: none;
    }

    .unopim-appurl-check-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .unopim-appurl-check {
        flex-shrink: 0;
        width: 18px;
        height: 18px;
        margin-top: 1px;
        border: 2px solid #cbd5e1;
        border-radius: 5px;
        background: #ffffff;
        position: relative;
        transition: all 0.2s ease;
    }

    html.dark .unopim-appurl-check {
        background: #1F1C30;
        border-color: #353061;
    }

    .unopim-appurl-check-input:checked + .unopim-appurl-check {
        background: #7c3aed;
        border-color: #7c3aed;
    }

    .unopim-appurl-check-input:checked + .unopim-appurl-check::after {
        content: '';
        position: absolute;
        left: 5px;
        top: 1px;
        width: 4px;
        height: 9px;
        border: solid #ffffff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    .unopim-appurl-step-body {
        flex: 1;
        min-width: 0;
    }

    .unopim-appurl-step-text {
        display: block;
        font-size: 13px;
        color: #475569;
        transition: opacity 0.2s ease;
    }

    html.dark .unopim-appurl-step-text {
        color: #cbd5e1;
    }

    .unopim-appurl-check-input:checked ~ .unopim-appurl-step-body .unopim-appurl-step-text {
        text-decoration: line-through;
        opacity: 0.5;
    }

    .unopim-appurl-code {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 6px;
        padding: 5px 6px 5px 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
    }

    html.dark .unopim-appurl-code {
        background: #1F1C30;
        border-color: #28273F;
    }

    .unopim-appurl-code-text {
        flex: 1;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        font-size: 11.5px;
        color: #0f172a;
        word-break: break-all;
        user-select: all;
    }

    html.dark .unopim-appurl-code-text {
        color: #e2e8f0;
    }

    .unopim-appurl-copy {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
        padding: 4px 9px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
        color: #7c3aed;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .unopim-appurl-copy svg {
        width: 13px;
        height: 13px;
    }

    .unopim-appurl-copy:hover {
        background: #f5f3ff;
        border-color: #ddd6fe;
    }

    html.dark .unopim-appurl-copy {
        background: #26283D;
        border-color: #353061;
        color: #c4b5fd;
    }

    .unopim-appurl-copy.is-copied {
        color: #15803d;
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .unopim-appurl-kbds {
        display: inline-block;
        margin-top: 6px;
    }

    .unopim-appurl-kbd {
        display: inline-block;
        padding: 2px 6px;
        font-family: inherit;
        font-size: 11px;
        color: #334155;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        box-shadow: 0 1px 0 rgba(0,0,0,0.08);
    }

    html.dark .unopim-appurl-kbd {
        color: #e2e8f0;
        background: #1F1C30;
        border-color: #353061;
    }
</style>

<script>
    function unopimAppurlEls() {
        return {
            backdrop: document.getElementById('unopim-appurl-backdrop'),
        };
    }

    function unopimAppurlShow() {
        var els = unopimAppurlEls();
        if (els.backdrop) els.backdrop.classList.add('is-open');
    }

    function unopimAppurlHideAll() {
        var els = unopimAppurlEls();
        if (els.backdrop) els.backdrop.classList.remove('is-open');
    }

    function unopimAppurlDismiss(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        var els = unopimAppurlEls();
        var url = els.backdrop ? els.backdrop.dataset.checkUrl : '';

        // No endpoint to validate against: just hide (refresh re-checks server-side).
        if (! url) {
            unopimAppurlHideAll();
            return;
        }

        var btn = event && event.currentTarget ? event.currentTarget : null;
        if (btn) btn.disabled = true;

        // Re-validate APP_URL on the server before dismissing.
        fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.matches) {
                    // APP_URL now matches: reload so the (now correct) assets load
                    // and the modal is gone for good.
                    window.location.reload();
                } else {
                    if (btn) btn.disabled = false;
                    unopimAppurlToast('APP_URL still does not match. Update .env and run "php artisan optimize:clear", then try again.');
                }
            })
            .catch(function () {
                if (btn) btn.disabled = false;
                unopimAppurlToast('Could not verify APP_URL. Please refresh the page.');
            });
    }

    function unopimAppurlToast(message) {
        var existing = document.getElementById('unopim-appurl-toast');
        if (existing) existing.parentNode.removeChild(existing);

        var toast = document.createElement('div');
        toast.id = 'unopim-appurl-toast';
        toast.setAttribute('role', 'alert');
        toast.style.cssText = [
            'position:fixed', 'top:20px', 'left:50%', 'transform:translateX(-50%)',
            'z-index:2147483647', 'max-width:calc(100vw - 32px)', 'width:380px',
            'display:flex', 'align-items:flex-start', 'gap:10px',
            'padding:12px 14px', 'border-radius:10px',
            'background:#7f1d1d', 'color:#fff',
            "font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif",
            'font-size:12.5px', 'line-height:1.45', 'font-weight:500',
            'box-shadow:0 12px 30px -8px rgba(0,0,0,0.5)',
            'opacity:0', 'transition:opacity .2s ease, transform .2s ease'
        ].join(';');
        toast.innerHTML =
            '<svg viewBox="0 0 24 24" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;color:#fca5a5;" aria-hidden="true">'
            + '<path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>'
            + '<span style="flex:1;">' + message + '</span>';

        document.body.appendChild(toast);
        // force reflow then animate in
        void toast.offsetWidth;
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';

        setTimeout(function () {
            toast.style.opacity = '0';
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 220);
        }, 4000);
    }

    function unopimAppurlStep(step) {
        var checkbox = document.getElementById('unopim-appurl-step-' + step);
        if (checkbox) {
            sessionStorage.setItem('unopim-appurl-step-' + step, checkbox.checked ? 'true' : 'false');
        }
    }

    function unopimAppurlCopy(btn, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }

        var text = btn.getAttribute('data-copy') || '';
        var label = btn.querySelector('.unopim-appurl-copy-label');
        var original = label ? label.innerText : '';

        function done() {
            btn.classList.add('is-copied');
            if (label) label.innerText = 'Copied';
            setTimeout(function () {
                btn.classList.remove('is-copied');
                if (label) label.innerText = original;
            }, 2000);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(function () { unopimAppurlFallbackCopy(text, done); });
        } else {
            unopimAppurlFallbackCopy(text, done);
        }
    }

    function unopimAppurlFallbackCopy(text, done) {
        var area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.focus();
        area.select();
        try {
            if (document.execCommand('copy')) done();
        } catch (e) {}
        document.body.removeChild(area);
    }

    (function () {
        var els = unopimAppurlEls();
        if (! els.backdrop) return;

        if (els.backdrop.dataset.justLoggedIn === 'true') {
            for (var s = 1; s <= 3; s++) {
                sessionStorage.removeItem('unopim-appurl-step-' + s);
            }
        }

        // Always open on load: the server only injects this when APP_URL is
        // mismatched, so refreshing after a fix simply shows nothing.
        unopimAppurlShow();

        for (var i = 1; i <= 3; i++) {
            var checkbox = document.getElementById('unopim-appurl-step-' + i);
            if (checkbox) {
                checkbox.checked = sessionStorage.getItem('unopim-appurl-step-' + i) === 'true';
            }
        }
    })();
</script>
@endverbatim
