@pushOnce('scripts')
    <script type="module">
        const store = window.Vue.reactive({
            count: {},
            dirty: {},
            setCount(id, n) { this.count[id] = n; },
            getCount(id) { return this.count[id] ?? 0; },
            setDirty(id, val) { this.dirty[id] = !! val; },
            isDirty(id) { return !! this.dirty[id]; },
        });

        app.config.globalProperties.$productWorkspace = store;
    </script>
@endPushOnce
