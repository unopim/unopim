/**
 * This will track all the images and fonts for publishing.
 */
import.meta.glob(["../images/**", "../fonts/**"]);

/**
 * Main vue bundler.
 */
import { createApp } from "vue/dist/vue.esm-bundler";

import { HEADERS, EMITTER_EVENTS } from "./constants";

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
        onAjaxSubmit(values, { evt, setErrors }) {
            const form = evt?.target;

            if (! form) return;

            const buttons = form.querySelectorAll('button[type="submit"], button:not([type])');

            const toggleButtons = (disabled) => {
                buttons.forEach(button => {
                    button.disabled = disabled;
                    button.classList.toggle("opacity-50", disabled);
                    button.classList.toggle("cursor-not-allowed", disabled);
                });
            };

            toggleButtons(true);

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
                        window.location.href = data.redirect_url;

                        return;
                    }

                    toggleButtons(false);
                })
                .catch(error => {
                    toggleButtons(false);

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

    window.app = app;

    return app;
}

window.createAdminApp = createAdminApp;

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
