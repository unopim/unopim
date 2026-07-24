import { HEADERS, META, NAV_EVENTS } from '../constants';

export default function initAjaxNavigation() {
    if (window.__ajaxNavInitialised) {
        return;
    }

    window.__ajaxNavInitialised = true;

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

        if (url.pathname === window.location.pathname && url.hash) {
            return false;
        }

        return url.pathname.startsWith(adminPrefix());
    }

    function adminPrefix() {
        const meta = document.querySelector('meta[name="' + META.ADMIN_URL + '"]');

        const path = new URL((meta && meta.getAttribute('content')) || 'admin', window.location.origin).pathname;

        const marker = '/' + (path.split('/').filter(Boolean).pop() || 'admin') + '/';

        const here = window.location.pathname + '/';

        const at = here.indexOf(marker);

        return at !== -1 ? here.slice(0, at + marker.length) : '/' + path.replace(/^\/+|\/+$/g, '') + '/';
    }

    async function visit(url, push) {
        const before = dispatch(NAV_EVENTS.BEFORE, { url }, true);

        if (before.defaultPrevented) {
            window.location.assign(url);

            return;
        }

        toggleProgress(true);

        try {
            const response = await fetch(url, {
                headers: { [HEADERS.AJAX_NAV]: 'true' },
                credentials: 'same-origin',
                redirect: 'follow',
            });

            if (! response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

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

            replaceInjectedStyles(styles);

            window.createAdminApp();

            await injectScripts(scripts);

            window.app.mount('#app');

            document.title = doc.title;

            if (push) {
                window.history.pushState({ ajaxNav: true }, '', finalUrl);
            }

            (document.getElementById('main-content') ?? window).scrollTo(0, 0);

            dispatch(NAV_EVENTS.SUCCESS, { url });
        } catch (error) {
            dispatch(NAV_EVENTS.ERROR, { url, error });

            window.location.assign(url);

            return;
        } finally {
            toggleProgress(false);
        }
    }

    function dispatch(name, detail, cancelable = false) {
        const event = new CustomEvent(name, { detail, cancelable, bubbles: true });

        document.dispatchEvent(event);

        return event;
    }

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

                    script.onload = resolve;
                    script.onerror = resolve;

                    document.body.appendChild(script);
                }));
            }
        });

        await Promise.all(pending);

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
