export default {
    mounted(el, binding, vnode) {
        let handler = function (e) {
            setTimeout(function () {
                let value = e.target.value.toString()
                    .replace(/[^\w_ ]+/g, '')
                    .trim()
                    .replace(/ +/g, '_');
                if (value.length > 191) {
                    value = value.substring(0,191);
                }

                e.target.value = value;
            }, 100);
        }

        el.addEventListener('input', handler);
    }
}
