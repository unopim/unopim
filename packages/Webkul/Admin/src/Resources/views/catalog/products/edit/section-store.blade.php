@pushOnce('scripts')
    <script type="module">
        const store = window.Vue.reactive({
            count: {},
            dirty: {},
            setCount(id, n) { this.count[id] = n; },
            getCount(id) { return this.count[id] ?? 0; },
            setDirty(id, val) { this.dirty[id] = !! val; },
            isDirty(id) { return !! this.dirty[id]; },

            summary: {},
            setSummary(id, text) { this.summary[id] = text; },
            getSummary(id) { return this.summary[id] ?? ''; },

            view: {},
            setView(id, val) { this.view[id] = val; },
            getView(id) { return this.view[id] ?? 'browse'; },
            toggleView(id, val) { this.setView(id, this.getView(id) === val ? 'browse' : val); },
        });

        app.config.globalProperties.$productWorkspace = store;

        /**
         * Section dirt is client-side only, so nothing else would ever clear it and
         * a saved section would keep showing its unsaved marker.
         */
        app.config.globalProperties.$emitter?.on('form-saved', () => {
            Object.keys(store.dirty).forEach(id => store.dirty[id] = false);
        });
    </script>
@endPushOnce
