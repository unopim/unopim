/**
 * Admin UI interaction contract.
 *
 * Single source of truth for the header, attribute and event names that wire
 * the ajax form-save and ajax-navigation systems together. Import these instead
 * of hardcoding strings so the conventions stay discoverable and rename-safe.
 *
 * Blade caveat: `*.blade.php` and PHP cannot import this file. The matching
 * literals there — the `ajax` / `data-ajax-form` / `data-ajax-error-message`
 * attributes on `<x-admin::form>`, and the `X-Ajax-Form` check inside the
 * `ConvertAjaxFormRedirect` middleware — must be kept in step with the values
 * below.
 */

/**
 * Request headers.
 *
 * AJAX_FORM — marks an ajax form submit; the middleware converts the
 *   controller's redirect into JSON for it.
 * AJAX_NAV  — marks an ajax navigation fetch (informational).
 */
export const HEADERS = {
    AJAX_FORM: 'X-Ajax-Form',
    AJAX_NAV: 'X-Ajax-Nav',
};

/**
 * DOM attributes.
 *
 * NO_AJAX_NAV — put on an `<a>` to opt it out of ajax navigation.
 */
export const ATTRIBUTES = {
    AJAX_FORM: 'data-ajax-form',
    AJAX_ERROR_MESSAGE: 'data-ajax-error-message',
    NO_AJAX_NAV: 'data-no-ajax-nav',
};

/**
 * Global emitter (mitt) events — scoped to the current page/app instance.
 */
export const EMITTER_EVENTS = {
    ADD_FLASH: 'add-flash',
    FORM_SAVED: 'form-saved',
};

/**
 * Navigation lifecycle events, dispatched on `document` so any module — Vue or
 * plain JS, core or plugin — can listen. They survive the per-visit Vue
 * re-mount because they live on `document`, not on the app instance.
 *
 * BEFORE  — cancelable; `event.preventDefault()` forces a full page load.
 *           detail: { url }
 * SUCCESS — the new page is mounted and live; re-initialise non-Vue widgets
 *           here (Vue components re-run their own mounted() hooks). detail: { url }
 * ERROR   — a visit failed and fell back to a full load. detail: { url, error }
 */
export const NAV_EVENTS = {
    BEFORE: 'unopim:navigate:before',
    SUCCESS: 'unopim:navigate:success',
    ERROR: 'unopim:navigate:error',
};

/**
 * `<meta>` names read from the document head.
 */
export const META = {
    ADMIN_URL: 'admin-url',
};
