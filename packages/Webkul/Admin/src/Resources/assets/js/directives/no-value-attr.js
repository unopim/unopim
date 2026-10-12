/**
 * Keeps a sensitive input's live value out of the serialized DOM.
 *
 * Vue 3.5 mirrors a bound `value` into the DOM attribute on every patch, so a
 * typed password would be readable from outerHTML (extensions, saved pages,
 * DOM-capturing error reporters). Stripping the attribute after each patch
 * leaves the DOM property — and everything the user sees — untouched.
 */
export default {
    mounted(el) {
        el.removeAttribute("value");
    },

    updated(el) {
        el.removeAttribute("value");
    },
};
