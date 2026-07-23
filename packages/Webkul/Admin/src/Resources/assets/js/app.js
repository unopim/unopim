/**
 * This will track all the images and fonts for publishing.
 */
import.meta.glob(["../images/**", "../fonts/**"]);

/**
 * Main vue bundler.
 */
import { createApp } from "vue/dist/vue.esm-bundler";

import DOMPurify from "dompurify";

import { HEADERS, EMITTER_EVENTS } from "./constants";

// Expose DOMPurify for inline component scripts (e.g. AI chat widget); no CDN.
window.DOMPurify = DOMPurify;

/**
 * Main root application registry.
 */
const appOptions = {
    data() {
        return {};
    },

    methods: {
        onSubmit() {},

        onInvalidSubmit({ values, errors, results }) {
            setTimeout(() => {
                const errorKeys = Object.entries(errors)
                    .map(([key, value]) => ({ key, value }))
                    .filter(error => error["value"].length);

                if (! errorKeys.length) return;

                let firstErrorElement = document.querySelector('[name="' + errorKeys[0]["key"] + '"]');

                if (! firstErrorElement) return;

                firstErrorElement.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });

                setTimeout(() => {
                    firstErrorElement.focus();
                }, 500);
            }, 100);
        },

        /**
         * Submit an `ajax`-flagged form without navigating away. Posts the raw
         * FormData (so `_method` spoofing and file inputs are preserved), flashes
         * the server message, and maps Laravel 422 errors back onto the fields.
         */
        onAjaxSubmit(values, { evt, setErrors, setFieldValue }) {
            const form = evt?.target;

            if (! form) return;

            // Re-entrancy guard: a large payload keeps the request in flight long
            // enough for the user to click Save again (or hit Enter). Every submit
            // path funnels through here, so one flag on the form dedupes them all.
            if (form.dataset.ajaxSubmitting === "true") {
                return;
            }

            const submitSelector = 'button[type="submit"], button:not([type]), input[type="submit"]';

            const buttonSet = new Set(form.querySelectorAll('button[type="submit"], button:not([type])'));

            // On tracked forms the in-form save is removed and the real button is the
            // unsaved-changes bar's "Save changes" (type=button, outside the form).
            // Include it, plus any button associated via form="<id>", so the visible
            // button also disables while the save is in flight.
            const root = form.closest(".unsaved-root");

            if (root) {
                root.querySelectorAll("[data-unsaved-save]").forEach(el => buttonSet.add(el));
            }

            if (form.id) {
                document.querySelectorAll('[form="' + CSS.escape(form.id) + '"]').forEach(el => {
                    if (el.matches(submitSelector)) {
                        buttonSet.add(el);
                    }
                });
            }

            const buttons = Array.from(buttonSet);

            const setBusy = (busy) => {
                form.dataset.ajaxSubmitting = busy ? "true" : "false";

                buttons.forEach(button => {
                    button.disabled = busy;
                    button.classList.toggle("opacity-50", busy);
                    button.classList.toggle("cursor-not-allowed", busy);
                });
            };

            setBusy(true);

            this.$axios.post(form.getAttribute("action") || form.action, new FormData(form), {
                headers: {
                    "Accept": "application/json",
                    [HEADERS.AJAX_FORM]: "true",
                },
            })
                .then(({ data }) => {
                    if (data.message) {
                        this.$emitter.emit(EMITTER_EVENTS.ADD_FLASH, { type: "success", message: data.message });
                    }

                    this.$emitter.emit(EMITTER_EVENTS.FORM_SAVED, data);

                    if (data.redirect_url) {
                        this.$navigate(data.redirect_url);

                        return;
                    }

                    setBusy(false);
                })
                .catch(error => {
                    setBusy(false);

                    // No page reload on failure, so clear the password field instead of leaving the secret on screen.
                    form.querySelectorAll('input[autocomplete="current-password"]').forEach(input => {
                        if (input.name) {
                            setFieldValue(input.name, "");
                        }
                    });

                    const response = error.response;

                    if (response && response.status === 422 && response.data.errors) {
                        const errors = {};

                        Object.keys(response.data.errors).forEach(key => {
                            const name = key.replace(/\.([^.]+)/g, "[$1]");

                            errors[name] = Array.isArray(response.data.errors[key])
                                ? response.data.errors[key][0]
                                : response.data.errors[key];
                        });

                        setErrors(errors);

                        const firstField = Object.keys(errors)[0];

                        if (firstField) {
                            const element = form.querySelector('[name="' + CSS.escape(firstField) + '"]');

                            if (element) {
                                element.scrollIntoView({ behavior: "smooth", block: "center" });
                            }
                        }

                        // A custom 422 may carry `errors` without any `message`.
                        const validationMessage = errors[firstField]
                            || (response.data && response.data.message)
                            || form.dataset.ajaxErrorMessage;

                        if (validationMessage) {
                            this.$emitter.emit(EMITTER_EVENTS.ADD_FLASH, { type: "error", message: validationMessage });
                        }

                        return;
                    }

                    const message = (response && response.data && response.data.message)
                        || form.dataset.ajaxErrorMessage;

                    const type = (response && response.data && response.data.type) || "error";

                    this.$emitter.emit(EMITTER_EVENTS.ADD_FLASH, { type, message });
                });
        },
    },
};

/**
 * Global plugins registration.
 */
import Admin from "./plugins/admin";
import Axios from "./plugins/axios";
import CreateElement from "./plugins/createElement";
import Emitter from "./plugins/emitter";
import Flatpickr from "./plugins/flatpickr";
import VeeValidate from "./plugins/vee-validate";
import Draggable from "./plugins/draggable";
import Multiselect from './plugins/multiselect';
import Tribute from "./plugins/tribute";

/**
 * Global directives.
 */
import Slugify from "./directives/slugify";
import Debounce from "./directives/debounce";
import Code from "./directives/code";
import CodeGenerator from "./directives/code-generator";
import { generateCode, sanitizeCode } from "./utils/code";

/**
 * Ajax navigation (progressive enhancement).
 */
import initAjaxNavigation from "./plugins/navigation";

/**
 * Build a fresh application instance with every global plugin and directive
 * registered. It is exposed globally and left unmounted so that per-page
 * component registration scripts (pushed after `#app`) can register against
 * `window.app` before it is mounted. Ajax navigation re-invokes this on each
 * visit to rebuild the app over the swapped-in page.
 */
function createAdminApp() {
    const app = createApp(appOptions);

    [
        Admin,
        Axios,
        CreateElement,
        Emitter,
        Flatpickr,
        VeeValidate,
        Draggable,
        Multiselect,
        Tribute,
    ].forEach((plugin) => app.use(plugin));

    app.directive("slugify", Slugify);
    app.directive("debounce", Debounce);
    app.directive("code", Code);
    app.directive("code-generator", CodeGenerator);

    app.config.globalProperties.$generateCode = generateCode;
    app.config.globalProperties.$sanitizeCode = sanitizeCode;

    /**
     * Canonical post-action navigation helper, available on every component as
     * `this.$navigate(url)`. Prefers the SPA ajax-nav visit (no full reload);
     * falls back to a hard navigation when the nav layer is unavailable (e.g.
     * the anonymous login page). Every redirect-after-save should route through
     * this instead of assigning `window.location.href` directly.
     */
    app.config.globalProperties.$navigate = (url) => {
        if (window.unopim?.visit) {
            window.unopim.visit(url);
        } else {
            window.location.href = url;
        }
    };

    window.app = app;

    return app;
}

window.createAdminApp = createAdminApp;

// Ref-counted body scroll lock so a closing overlay doesn't restore page scroll while another is still open.
window.lockBodyScroll = () => {
    window.__scrollLocks = (window.__scrollLocks || 0) + 1;

    document.body.style.overflow = "hidden";
};

window.unlockBodyScroll = () => {
    window.__scrollLocks = Math.max(0, (window.__scrollLocks || 1) - 1);

    if (! window.__scrollLocks) {
        document.body.style.overflow = "";
    }
};

createAdminApp();

/**
 * Load event, the purpose of using the event is to mount the application
 * after all of our `Vue` components which is present in blade file have
 * been registered in the app. No matter what `app.mount()` should be
 * called in the last.
 */
window.addEventListener("load", function (event) {
    window.app.mount("#app");

    initAjaxNavigation();
});
