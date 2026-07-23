import { generateCode } from '../utils/code';

function resolveTarget(el, binding) {
    const value = binding.value;
    const target = typeof value === 'object' ? value.target : value;

    if (! target) {
        return null;
    }

    if (target instanceof HTMLElement) {
        return target;
    }

    if (typeof target !== 'string') {
        return null;
    }

    const root = el.closest('form') || document;
    const escapedTarget = CSS.escape(target);

    if (/^[.#[]/.test(target)) {
        return root.querySelector(target);
    }

    return root.querySelector(`[name="${escapedTarget}"]`)
        || root.querySelector(`#${escapedTarget}`);
}

export default {
    mounted(el, binding) {
        const target = resolveTarget(el, binding);

        if (! target) {
            return;
        }

        const state = {
            updating: false,
            manuallyChanged: false,
        };

        target.__unopimCodeGeneratorState = state;

        target.addEventListener('input', function () {
            if (! state.updating) {
                state.manuallyChanged = true;
            }
        });

        el.addEventListener('input', function (event) {
            if (state.manuallyChanged) {
                return;
            }

            state.updating = true;
            target.value = generateCode(event.target.value);
            target.dispatchEvent(new Event('input', { bubbles: true }));
            state.updating = false;
        });
    },
};
