/**
 * Focuses the element on insert (e.g. an input revealed behind a `v-if` toggle).
 * Skips when bound to `false`.
 */
export default {
    mounted(el, binding) {
        if (binding.value === false) {
            return;
        }

        el.focus();
    },
};
