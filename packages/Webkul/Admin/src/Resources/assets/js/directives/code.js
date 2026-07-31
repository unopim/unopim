import { sanitizeCode } from '../utils/code';

export default {
    mounted(el, binding, vnode) {
        let handler = function (e) {
            setTimeout(function () {
                e.target.value = sanitizeCode(e.target.value);
            }, 100);
        }

        el.addEventListener('input', handler);
    }
}
