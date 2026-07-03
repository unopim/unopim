/**
 * Progressive-enhancement admin navigation.
 *
 * Instead of a full document reload, an internal admin link is fetched over
 * ajax and only the `#app` region is swapped in, keeping the loaded JS runtime
 * and already-cached assets alive and removing the white flash. The server
 * still returns the whole page, so the sidebar, active menu state, header and
 * tabs all come back correct for free.
 *
 * This is strictly additive: ANY failure (network, parse, missing #app, a
 * script that throws while re-registering) falls back to a normal full
 * navigation, so a page can only ever be made smoother, never broken.
 */
import { HEADERS, META, NAV_EVENTS } from '../constants';

export default function initAjaxNavigation() {
    if (window.__ajaxNavInitialised) {
        return;
    }

    window.__ajaxNavInitialised = true;

    // Public programmatic navigation, for JS-driven links that are not plain
    // anchors (e.g. datagrid row actions). Falls back to a full load on failure
    // like every other visit.
    window.unopim = window.unopim || {};
    window.unopim.visit = (url) => visit(url, true);

    document.addEventListener('click', onDocumentClick, true);

    window.addEventListener('popstate', () => visit(window.location.href, false));

    function onDocumentClick(event) {
        if (
            event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
        ) {
            return;
        }

        const link = event.target.closest ? event.target.closest('a[href]') : null;

        if (! link || ! isEligible(link)) {
            return;
        }

        event.preventDefault();

        visit(link.href, true);
    }

    function isEligible(link) {
        if (link.target && link.target !== '' && link.target !== '_self') {
            return false;
        }

        if (link.hasAttribute('download') || link.dataset.noAjaxNav !== undefined) {
            return false;
        }

        const url = new URL(link.href, window.location.origin);

        if (url.origin !== window.location.origin) {
            return false;
        }

        // Pure in-page anchors are the browser's job.
        if (url.pathname === window.location.pathname && url.hash) {
            return false;
        }

        return url.pathname.startsWith(adminPrefix());
    }

    function adminPrefix() {
        const meta = document.querySelector('meta[name="' + META.ADMIN_URL + '"]');

        const configured = meta ? meta.getAttribute('content') : '';

        const prefix = (configured || window.location.pathname.split('/').filter(Boolean)[0] || 'admin')
            .replace(/^\/+|\/+$/g, '');

        return '/' + prefix + '/';
    }

    async function visit(url, push) {
        // Public, cancelable lifecycle hook: a module can call
        // event.preventDefault() to force a normal full navigation for a case
        // it cannot handle over ajax.
        const before = dispatch(NAV_EVENTS.BEFORE, { url }, true);

        if (before.defaultPrevented) {
            window.location.assign(url);

            return;
        }

        toggleProgress(true);

        try {
            // Use fetch, not axios: axios sets X-Requested-With globally, which
            // makes controllers return their datagrid JSON instead of the full
            // page. A plain fetch reads as a normal navigation and returns HTML.
            const response = await fetch(url, {
                headers: { [HEADERS.AJAX_NAV]: 'true' },
                credentials: 'same-origin',
                redirect: 'follow',
            });

            if (! response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

            // Follow the server's redirects in the address bar too. When the
            // requested URL 302s (e.g. ai-agent/settings → configuration/...),
            // `response.url` is the final URL; pushing the original would leave
            // the bar stale so a page form with `action=""` posts to the wrong
            // route (405). Fall back to the request URL when unavailable.
            const finalUrl = response.url || url;

            const html = await response.text();

            const doc = new DOMParser().parseFromString(html, 'text/html');

            const nextApp = doc.querySelector('#app');

            if (! nextApp) {
                throw new Error('Missing #app in response');
            }

            const scripts = collectPageScripts(doc);

            const styles = [...doc.body.querySelectorAll('style')];

            if (window.app && typeof window.app.unmount === 'function') {
                window.app.unmount();
            }

            document.querySelector('#app').replaceWith(nextApp);

            removePageScripts();

            // Page-specific inline styles (e.g. the unsaved-changes bar) live in
            // `@push('scripts')`; without this the swapped page loses that CSS.
            replaceInjectedStyles(styles);

            window.createAdminApp();

            await injectScripts(scripts);

            window.app.mount('#app');

            document.title = doc.title;

            if (push) {
                window.history.pushState({ ajaxNav: true }, '', finalUrl);
            }

            window.scrollTo(0, 0);

            // The new page is mounted and live: modules re-initialise any
            // non-Vue widgets (third-party libraries, charts, editors) here.
            // Vue components re-run their own mounted() hooks automatically.
            dispatch(NAV_EVENTS.SUCCESS, { url });
        } catch (error) {
            dispatch(NAV_EVENTS.ERROR, { url, error });

            window.location.assign(url);

            return;
        } finally {
            toggleProgress(false);
        }
    }

    /**
     * Fire a public navigation lifecycle event on `document`. Any code — Vue or
     * plain JS, core or plugin — can listen without depending on the Vue app
     * instance, which is torn down and rebuilt on every visit.
     */
    function dispatch(name, detail, cancelable = false) {
        const event = new CustomEvent(name, { detail, cancelable, bubbles: true });

        document.dispatchEvent(event);

        return event;
    }

    /**
     * The persistent app bundle lives in <head>; every <script> in <body> is a
     * per-page `@stack('scripts')` registration (component definitions and their
     * `text/x-template` blocks) that must be re-run against the fresh app.
     */
    function collectPageScripts(doc) {
        return [...doc.body.querySelectorAll('script')].filter((script) => {
            return ! script.src || ! /assets\/app-.*\.js/.test(script.src);
        });
    }

    function isExecutableType(type) {
        return type === ''
            || type === 'text/javascript'
            || type === 'application/javascript'
            || type === 'module';
    }

    /**
     * Drop the previous page's inline scripts and templates (they have already
     * run / are about to be replaced). External library `<script src>` nodes are
     * kept so a lib such as TinyMCE is not re-fetched and re-run on every visit.
     */
    /**
     * Swap in the incoming page's inline `<style>` blocks. Previously-injected
     * ones are dropped first so styles do not accumulate across visits. The
     * persistent bundle stylesheet (a `<link>` in <head>) is never touched.
     */
    function replaceInjectedStyles(styles) {
        document.querySelectorAll('style[data-ajax-nav-style]').forEach((node) => node.remove());

        styles.forEach((original) => {
            const style = original.cloneNode(true);

            style.setAttribute('data-ajax-nav-style', '');

            document.head.appendChild(style);
        });
    }

    function removePageScripts() {
        [...document.body.querySelectorAll('script')].forEach((node) => {
            if (node.closest('#app') || node.src) {
                return;
            }

            node.remove();
        });
    }

    async function injectScripts(scripts) {
        const pending = [];

        // Pass 1: put templates/JSON into the DOM and start loading external
        // libraries (deduped). Inline scripts wait until the libs are ready so a
        // component's init (e.g. TinyMCE) can rely on the global being present.
        scripts.forEach((original) => {
            const type = (original.getAttribute('type') || '').toLowerCase();

            if (! isExecutableType(type)) {
                document.body.appendChild(original.cloneNode(true));

                return;
            }

            if (original.src) {
                if (document.querySelector('script[src="' + original.src + '"]')) {
                    return;
                }

                pending.push(new Promise((resolve) => {
                    const script = document.createElement('script');
                    script.src = original.src;

                    if (type) {
                        script.type = type;
                    }

                    // Never let a slow or failing library block navigation.
                    script.onload = resolve;
                    script.onerror = resolve;

                    document.body.appendChild(script);
                }));
            }
        });

        await Promise.all(pending);

        // Pass 2: run inline scripts in document order, each in its OWN function
        // scope so page-level `const`/`let`/`function` declarations cannot
        // collide when the same script runs again on a later navigation. A
        // throwing script is logged, never allowed to break navigation.
        scripts.forEach((original) => {
            const type = (original.getAttribute('type') || '').toLowerCase();

            if (! isExecutableType(type) || original.src) {
                return;
            }

            try {
                new Function(original.textContent)();
            } catch (error) {
                console.error('Ajax navigation: a page script failed to run', error);
            }
        });
    }

    function toggleProgress(active) {
        let bar = document.getElementById('ajax-nav-progress');

        if (active) {
            if (! bar) {
                bar = document.createElement('div');
                bar.id = 'ajax-nav-progress';
                bar.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0;'
                    + 'background:#7c3aed;z-index:100000;transition:width .2s ease,opacity .3s ease;';
                document.body.appendChild(bar);
            }

            bar.style.opacity = '1';
            bar.style.width = '80%';
        } else if (bar) {
            bar.style.width = '100%';
            bar.style.opacity = '0';
        }
    }
}
