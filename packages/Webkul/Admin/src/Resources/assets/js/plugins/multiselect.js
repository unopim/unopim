import Multiselect from 'vue-multiselect'

export default {
    install: (app) => {
        app.component('VMultiselect', Multiselect);
    },
}
