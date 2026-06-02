{{--
    APP_URL mismatch warning — UnoPim "guided steps" modal, fully self-contained.

    Rendered by Webkul\AppUrlGuard\Http\Middleware\VerifyAppUrlMatches and
    injected before </body> in debug mode only.

    IMPORTANT:
      - Every class is prefixed (unopim-appurl-*) and the CSS variables are
        scoped to #unopim-appurl-backdrop so nothing collides with the host
        page's styles.
      - Icons are inline SVG (never the icomoon font, which 404s on a mismatched
        APP_URL — the very problem this modal warns about).
      - Functionality is unchanged: the close (×) button re-validates APP_URL on
        the server and either reloads (fixed) or shows a toast (still wrong); the
        copy buttons copy + tick their step; "All done" hard-reloads the page.

    Props: $configured (APP_URL in .env), $actual (host the browser is on),
           $checkUrl (same-origin re-validation endpoint),
           $justLoggedIn (reset the step state right after login).
--}}
<div
    id="unopim-appurl-backdrop"
    class="unopim-appurl-backdrop"
    data-just-logged-in="{{ $justLoggedIn ? 'true' : 'false' }}"
    data-check-url="{{ $checkUrl }}"
>
    <div id="unopim-appurl-warning" class="unopim-appurl-modal" role="alertdialog" aria-modal="true" aria-labelledby="unopim-appurl-title">
        <div class="unopim-appurl-head">
            <span class="unopim-appurl-head-icon" aria-hidden="true">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>
            </span>

            <h1 class="unopim-appurl-head-title" id="unopim-appurl-title">@lang('APP_URL Mismatch Detected')</h1>

            <button type="button" class="unopim-appurl-head-close" aria-label="@lang('Dismiss')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="unopim-appurl-body">
            <p class="unopim-appurl-lede">
                @lang('Your frontend assets (CSS, JS) are pinned to the configured')
                <b>APP_URL</b>.
                @lang('Update it to match the host you are using, otherwise the styles and scripts will not load.')
            </p>

            <div class="unopim-appurl-compare">
                <div class="unopim-appurl-cmp unopim-appurl-cmp--bad">
                    <div class="unopim-appurl-cmp-left">
                        <span class="unopim-appurl-cmp-label">@lang('Configured (.env)')</span>
                        <span class="unopim-appurl-cmp-tag">@lang('MISMATCH')</span>
                    </div>
                    <span class="unopim-appurl-cmp-url">{{ $configured }}</span>
                </div>

                <div class="unopim-appurl-cmp unopim-appurl-cmp--good">
                    <div class="unopim-appurl-cmp-left">
                        <span class="unopim-appurl-cmp-label">@lang('Actual (browser)')</span>
                        <span class="unopim-appurl-cmp-tag">@lang('IN USE')</span>
                    </div>
                    <span class="unopim-appurl-cmp-url">{{ $actual }}</span>
                </div>
            </div>

            <div class="unopim-appurl-steps">
                <div class="unopim-appurl-step" data-unopim-step="1">
                    <button type="button" class="unopim-appurl-step-badge" aria-label="@lang('Toggle step 1')">
                        <span class="unopim-appurl-step-n">1</span>
                        <svg class="unopim-appurl-step-tick" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                    </button>
                    <div class="unopim-appurl-step-main">
                        <div class="unopim-appurl-step-title">@lang('Update APP_URL in your .env file')
                            <span class="unopim-appurl-step-hint">@lang("Open the project's .env and replace the APP_URL line.")</span>
                        </div>
                        <div class="unopim-appurl-code">
                            <code class="unopim-appurl-code-text"><span class="unopim-appurl-tok">APP_URL=</span>{{ $actual }}</code>
                            <button type="button" class="unopim-appurl-code-copy" data-copy="APP_URL={{ $actual }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></svg>
                                <span class="unopim-appurl-copy-label">@lang('Copy')</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="unopim-appurl-step" data-unopim-step="2">
                    <button type="button" class="unopim-appurl-step-badge" aria-label="@lang('Toggle step 2')">
                        <span class="unopim-appurl-step-n">2</span>
                        <svg class="unopim-appurl-step-tick" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                    </button>
                    <div class="unopim-appurl-step-main">
                        <div class="unopim-appurl-step-title">@lang('Clear the application cache')
                            <span class="unopim-appurl-step-hint">@lang('Run this in your terminal from the project root.')</span>
                        </div>
                        <div class="unopim-appurl-code">
                            <code class="unopim-appurl-code-text">php artisan optimize:clear</code>
                            <button type="button" class="unopim-appurl-code-copy" data-copy="php artisan optimize:clear">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></svg>
                                <span class="unopim-appurl-copy-label">@lang('Copy')</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="unopim-appurl-note">
                <span class="unopim-appurl-note-ico" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
                </span>
                <div class="unopim-appurl-note-body">
                    <b>@lang('Then hard refresh the page')</b> @lang('so the browser reloads the updated assets.')
                    <span class="unopim-appurl-combo">
                        <span class="unopim-appurl-kbd">Ctrl</span><span class="unopim-appurl-plus">+</span><span class="unopim-appurl-kbd">Shift</span><span class="unopim-appurl-plus">+</span><span class="unopim-appurl-kbd">R</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="unopim-appurl-foot">
            <div class="unopim-appurl-progress-wrap">
                <div class="unopim-appurl-progress-label" id="unopim-appurl-progress-label">@lang('0 of 2 steps complete')</div>
                <div class="unopim-appurl-progress-track"><div class="unopim-appurl-progress-fill" id="unopim-appurl-progress-fill"></div></div>
            </div>
            <button type="button" class="unopim-appurl-btn-done" id="unopim-appurl-btn-done" disabled>@lang('All done')</button>
        </div>
    </div>

    <div class="unopim-appurl-poweredby">
        @lang('Powered by') <a href="https://unopim.com/" target="_blank" rel="noopener">UnoPim</a>.<br>
        @lang('An open-source project by') <a href="https://webkul.com/" target="_blank" rel="noopener">Webkul</a>.
    </div>
</div>

<div class="unopim-appurl-toast" id="unopim-appurl-toast" role="alert">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
    <span id="unopim-appurl-toast-msg"></span>
</div>

@verbatim
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');

    #unopim-appurl-backdrop {
        --brand-600: #6553EB;
        --brand-700: #5641D9;
        --brand-50:  #F0EDFC;
        --brand-100: #E3DEF9;
        --bg-card:   #FFFFFF;
        --bg-soft:   #FAF9FE;
        --bg-strip:  #F8F6FD;
        --ink-900: #1A1530;
        --ink-700: #3C3656;
        --ink-500: #6B6584;
        --ink-400: #8C87A3;
        --ink-300: #B6B2C7;
        --line:    #E8E4F2;
        --line-2:  #EFECF6;
        --danger-600: #EF4444;
        --danger-700: #DC2626;
        --danger-50:  #FEF2F2;
        --danger-100: #FEE2E2;
        --success-700: #15803D;
        --success-600: #16A34A;
        --success-50:  #F0FDF4;
        --success-100: #DCFCE7;
        --warn-600: #F59E0B;
        --warn-50:  #FFFBEB;
        --warn-100: #FEF3C7;
        --r-sm: 6px; --r-md: 10px; --r-lg: 14px; --r-xl: 18px;
        --shadow-lg: 0 24px 60px rgba(20, 16, 40, 0.30), 0 6px 20px rgba(20, 16, 40, 0.10);
        --uafont: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
        --uamono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

        display: none;
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        padding: 18px;
        background: #F5F3FF;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        overflow: auto;
        font-family: var(--uafont);
        color: var(--ink-900);
        -webkit-font-smoothing: antialiased;
    }

    html.dark #unopim-appurl-backdrop { background: #1F1C30; }

    #unopim-appurl-backdrop.is-open { display: flex; }
    #unopim-appurl-backdrop *,
    #unopim-appurl-toast * { box-sizing: border-box; }

    .unopim-appurl-modal {
        width: 430px;
        max-width: 100%;
        max-height: calc(100vh - 90px);
        display: flex;
        flex-direction: column;
        background: var(--bg-card);
        border-radius: var(--r-xl);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        animation: unopimAppurlPop .2s cubic-bezier(.2,.7,.3,1);
    }
    @keyframes unopimAppurlPop { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }

    .unopim-appurl-head {
        display: flex; align-items: center; gap: 10px;
        flex: 0 0 auto;
        padding: 14px 16px;
        border-bottom: 1px solid var(--line-2);
    }
    .unopim-appurl-head-icon {
        width: 30px; height: 30px; border-radius: 8px;
        background: var(--warn-50); border: 1px solid var(--warn-100);
        display: flex; align-items: center; justify-content: center;
        color: var(--warn-600); flex: 0 0 auto;
    }
    .unopim-appurl-head-title {
        font-size: 15px; font-weight: 800; letter-spacing: -0.015em;
        flex: 1; min-width: 0; margin: 0; color: var(--ink-900);
    }
    .unopim-appurl-head-close {
        width: 30px; height: 30px; border-radius: 8px;
        border: 1px solid var(--line); background: var(--bg-strip);
        color: var(--ink-500); cursor: pointer; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center;
        transition: background .12s, color .12s, border-color .12s;
    }
    .unopim-appurl-head-close:hover { background: var(--danger-50); color: var(--danger-600); border-color: var(--danger-100); }
    .unopim-appurl-head-close:disabled { opacity: .5; cursor: progress; }

    .unopim-appurl-body { padding: 16px 18px 4px; overflow: auto; }

    .unopim-appurl-lede { font-size: 13px; line-height: 1.55; color: var(--ink-500); margin: 0 0 14px; }
    .unopim-appurl-lede b { color: var(--ink-700); font-weight: 700; }

    .unopim-appurl-compare {
        display: flex; flex-direction: column; gap: 8px;
        margin-bottom: 16px; padding: 10px;
        background: var(--bg-strip); border: 1px solid var(--line-2); border-radius: var(--r-lg);
    }
    .unopim-appurl-cmp {
        background: #fff; border-radius: var(--r-md); padding: 10px 12px;
        border: 1px solid var(--line);
        display: flex; flex-direction: column; gap: 6px; min-width: 0;
    }
    .unopim-appurl-cmp-left { display: flex; align-items: center; gap: 8px; }
    .unopim-appurl-cmp-label { font-size: 10.5px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; color: var(--ink-500); }
    .unopim-appurl-cmp-tag {
        display: inline-flex; align-items: center; height: 18px; padding: 0 7px;
        border-radius: 5px; font-size: 9px; font-weight: 800; letter-spacing: 0.05em;
    }
    .unopim-appurl-cmp--bad .unopim-appurl-cmp-tag { background: var(--danger-50); color: var(--danger-700); border: 1px solid var(--danger-100); }
    .unopim-appurl-cmp--good .unopim-appurl-cmp-tag { background: var(--success-50); color: var(--success-700); border: 1px solid var(--success-100); }
    .unopim-appurl-cmp-url { font-family: var(--uamono); font-size: 12px; line-height: 1.5; color: var(--ink-900); word-break: break-all; }
    .unopim-appurl-cmp--bad .unopim-appurl-cmp-url { color: var(--ink-400); text-decoration: line-through; text-decoration-color: var(--danger-100); }
    .unopim-appurl-cmp--bad { border-left: 3px solid var(--danger-600); }
    .unopim-appurl-cmp--good { border-left: 3px solid var(--success-600); }

    .unopim-appurl-steps { display: flex; flex-direction: column; gap: 10px; }
    .unopim-appurl-step {
        display: grid; grid-template-columns: 28px 1fr; gap: 11px; align-items: start;
        padding: 11px 13px; border: 1px solid var(--line); border-radius: var(--r-md);
        background: var(--bg-soft); transition: border-color .14s, background .14s;
    }
    .unopim-appurl-step.is-done { background: var(--bg-strip); border-color: var(--line-2); }
    .unopim-appurl-step-badge {
        width: 28px; height: 28px; border-radius: 50%; border: none; cursor: pointer; flex: 0 0 auto;
        background: var(--brand-50); color: var(--brand-700);
        font-family: var(--uafont); font-size: 13px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; position: relative;
        transition: background .14s, color .14s, transform .08s;
    }
    .unopim-appurl-step-badge:hover { transform: scale(1.06); }
    .unopim-appurl-step-n { transition: opacity .14s; }
    .unopim-appurl-step-tick { position: absolute; opacity: 0; transform: scale(.6); transition: opacity .14s, transform .14s; }
    .unopim-appurl-step.is-done .unopim-appurl-step-badge { background: var(--brand-600); color: #fff; }
    .unopim-appurl-step.is-done .unopim-appurl-step-n { opacity: 0; }
    .unopim-appurl-step.is-done .unopim-appurl-step-tick { opacity: 1; transform: none; }
    .unopim-appurl-step-main { min-width: 0; }
    .unopim-appurl-step-title {
        font-size: 13.5px; font-weight: 700; color: var(--ink-900);
        margin: 0 0 9px; letter-spacing: -0.01em; line-height: 1.35; padding-top: 3px; cursor: pointer;
    }
    .unopim-appurl-step.is-done .unopim-appurl-step-title { color: var(--ink-400); }
    .unopim-appurl-step-hint { display: block; font-size: 11.5px; font-weight: 500; color: var(--ink-400); margin-top: 3px; letter-spacing: 0; }

    .unopim-appurl-code {
        display: flex; align-items: stretch; gap: 0;
        border: 1px solid var(--line); border-radius: var(--r-md); background: var(--bg-strip); overflow: hidden;
    }
    .unopim-appurl-code-text {
        flex: 1; min-width: 0; font-family: var(--uamono); font-size: 12px; line-height: 1.5;
        color: var(--ink-900); padding: 9px 12px; white-space: nowrap; overflow-x: auto; overflow-y: hidden;
        display: flex; align-items: center; scrollbar-width: thin; scrollbar-color: var(--line) transparent;
    }
    .unopim-appurl-code-text::-webkit-scrollbar { height: 4px; }
    .unopim-appurl-code-text::-webkit-scrollbar-track { background: transparent; }
    .unopim-appurl-code-text::-webkit-scrollbar-thumb { background: var(--line); border-radius: 999px; }
    .unopim-appurl-tok { color: var(--brand-700); font-weight: 600; white-space: nowrap; }
    .unopim-appurl-code-copy {
        flex: 0 0 auto; align-self: stretch; display: inline-flex; align-items: center; gap: 6px;
        padding: 0 12px; border: none; border-left: 1px solid var(--line);
        background: #fff; color: var(--brand-700);
        font-family: var(--uafont); font-size: 12px; font-weight: 700; cursor: pointer;
        transition: background .12s, color .12s; white-space: nowrap;
    }
    .unopim-appurl-code-copy:hover { background: var(--brand-50); }
    .unopim-appurl-code-copy.is-copied { color: var(--success-600); }

    .unopim-appurl-note { display: flex; align-items: flex-start; gap: 9px; margin-top: 12px; padding: 0 2px; }
    .unopim-appurl-note-ico { display: inline-flex; color: var(--ink-400); flex: 0 0 auto; margin-top: 1px; }
    .unopim-appurl-note-body { font-size: 12.5px; line-height: 1.5; color: var(--ink-500); }
    .unopim-appurl-note-body b { color: var(--ink-700); font-weight: 700; }
    .unopim-appurl-combo { display: flex; align-items: center; gap: 6px; margin-top: 8px; }
    .unopim-appurl-kbd {
        display: inline-flex; align-items: center; justify-content: center; min-width: 27px; height: 25px;
        padding: 0 8px; background: #fff; border: 1px solid var(--line); border-bottom-width: 2px;
        border-radius: 6px; font-size: 11.5px; font-weight: 700; color: var(--ink-700); font-family: var(--uafont);
    }
    .unopim-appurl-plus { color: var(--ink-300); font-weight: 700; font-size: 12px; }

    .unopim-appurl-foot {
        display: flex; align-items: center; gap: 14px; flex: 0 0 auto;
        padding: 12px 18px; margin-top: 14px; border-top: 1px solid var(--line-2); background: var(--bg-strip);
    }
    .unopim-appurl-progress-wrap { flex: 1; min-width: 0; }
    .unopim-appurl-progress-label { font-size: 11px; font-weight: 700; color: var(--ink-500); margin-bottom: 5px; }
    .unopim-appurl-progress-track { height: 6px; border-radius: 999px; background: var(--brand-100); overflow: hidden; }
    .unopim-appurl-progress-fill { height: 100%; width: 0%; border-radius: 999px; background: var(--brand-600); transition: width .25s cubic-bezier(.2,.7,.3,1); }
    .unopim-appurl-btn-done {
        height: 36px; padding: 0 16px; border-radius: 9px; border: none;
        background: var(--brand-600); color: #fff; font-family: var(--uafont);
        font-size: 13px; font-weight: 700; cursor: pointer; transition: background .12s, opacity .12s; flex: 0 0 auto;
    }
    .unopim-appurl-btn-done:hover { background: var(--brand-700); }
    .unopim-appurl-btn-done:disabled { opacity: .45; cursor: not-allowed; }

    .unopim-appurl-poweredby {
        flex: 0 0 auto; text-align: center;
        font-size: 12px; line-height: 1.6; font-family: var(--uafont); font-weight: 500;
        color: #1F2937;
    }
    .unopim-appurl-poweredby a { color: #6D28D9; font-weight: 700; text-decoration: none; }
    .unopim-appurl-poweredby a:hover { text-decoration: underline; }
    html.dark .unopim-appurl-poweredby { color: #FFFFFF; }
    html.dark .unopim-appurl-poweredby a { color: #C4B5FD; }

    .unopim-appurl-toast {
        position: fixed; left: 50%; bottom: 28px; transform: translateX(-50%) translateY(20px);
        z-index: 2147483647; background: #1A1530; color: #fff;
        font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
        font-size: 13px; font-weight: 600; padding: 10px 16px; border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 8px;
        opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s;
    }
    .unopim-appurl-toast.is-show { opacity: 1; transform: translateX(-50%) translateY(0); }
    .unopim-appurl-toast svg { color: #6EE7A8; flex: 0 0 auto; }
</style>

<script>
    (function () {
        var backdrop = document.getElementById('unopim-appurl-backdrop');
        if (! backdrop) return;

        var modal = document.getElementById('unopim-appurl-warning');
        var steps = Array.prototype.slice.call(modal.querySelectorAll('[data-unopim-step]'));
        var fill = document.getElementById('unopim-appurl-progress-fill');
        var label = document.getElementById('unopim-appurl-progress-label');
        var btnDone = document.getElementById('unopim-appurl-btn-done');
        var toast = document.getElementById('unopim-appurl-toast');
        var toastMsg = document.getElementById('unopim-appurl-toast-msg');
        var toastTimer;

        function showToast(msg) {
            toastMsg.textContent = msg;
            toast.classList.add('is-show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { toast.classList.remove('is-show'); }, 2400);
        }

        function stepKey(step) {
            return 'unopim-appurl-step-' + step.getAttribute('data-unopim-step');
        }

        function updateProgress() {
            var done = steps.filter(function (s) { return s.classList.contains('is-done'); }).length;
            fill.style.width = (steps.length ? (done / steps.length * 100) : 0) + '%';
            label.textContent = done + ' of ' + steps.length + ' steps complete';
            btnDone.disabled = done < steps.length;
        }

        function markStep(step, done) {
            step.classList.toggle('is-done', done);
            try { sessionStorage.setItem(stepKey(step), done ? 'true' : 'false'); } catch (e) {}
            updateProgress();
        }

        function fallbackCopy(text, apply) {
            var area = document.createElement('textarea');
            area.value = text;
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.focus();
            area.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(area);
            apply();
        }

        // Step toggling — badge OR title.
        steps.forEach(function (step) {
            var badge = step.querySelector('.unopim-appurl-step-badge');
            var title = step.querySelector('.unopim-appurl-step-title');
            function toggle() { markStep(step, ! step.classList.contains('is-done')); }
            if (badge) badge.addEventListener('click', toggle);
            if (title) title.addEventListener('click', toggle);
        });

        // Copy buttons — copy, tick the step, toast.
        modal.querySelectorAll('.unopim-appurl-code-copy').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy') || '';
                var labelSpan = btn.querySelector('.unopim-appurl-copy-label');
                var apply = function () {
                    btn.classList.add('is-copied');
                    if (labelSpan) labelSpan.textContent = 'Copied';
                    showToast('Copied to clipboard');
                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                        if (labelSpan) labelSpan.textContent = 'Copy';
                    }, 1600);
                    var step = btn.closest('[data-unopim-step]');
                    if (step && ! step.classList.contains('is-done')) { markStep(step, true); }
                };
                if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(apply, function () { fallbackCopy(text, apply); });
                } else {
                    fallbackCopy(text, apply);
                }
            });
        });

        // Close (×) — re-validate APP_URL on the server before dismissing.
        var closeBtn = modal.querySelector('.unopim-appurl-head-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                var url = backdrop.getAttribute('data-check-url');
                if (! url) { backdrop.classList.remove('is-open'); return; }

                closeBtn.disabled = true;
                fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.matches) {
                            window.location.reload();
                        } else {
                            closeBtn.disabled = false;
                            showToast('APP_URL still does not match. Update .env and run "php artisan optimize:clear".');
                        }
                    })
                    .catch(function () {
                        closeBtn.disabled = false;
                        showToast('Could not verify APP_URL. Please refresh the page.');
                    });
            });
        }

        // "All done" — hard reload so the (hopefully fixed) assets are fetched.
        btnDone.addEventListener('click', function () {
            if (btnDone.disabled) return;
            window.location.reload();
        });

        // Restore / reset step state, then reveal.
        var justLoggedIn = backdrop.getAttribute('data-just-logged-in') === 'true';
        steps.forEach(function (step) {
            try {
                if (justLoggedIn) {
                    sessionStorage.removeItem(stepKey(step));
                } else if (sessionStorage.getItem(stepKey(step)) === 'true') {
                    step.classList.add('is-done');
                }
            } catch (e) {}
        });

        updateProgress();
        backdrop.classList.add('is-open');
    })();
</script>
@endverbatim
