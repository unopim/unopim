<div class="mt-5 max-w-[720px]">
    <x-admin::search
        name="settings_search"
        data-settings-search
        :placeholder="trans('admin::app.settings.system-settings.search-placeholder')"
    />
</div>

@pushOnce('scripts')
    <script>
        // Delegated on document so it survives the search field's Vue re-render;
        // filters the [data-settings-row] rows and hides sections left empty.
        document.addEventListener('input', (event) => {
            if (! event.target.matches('[data-settings-search]')) {
                return;
            }

            const query = event.target.value.trim().toLowerCase();

            let totalVisible = 0;

            document.querySelectorAll('[data-settings-section]').forEach((section) => {
                let anyVisible = false;

                section.querySelectorAll('[data-settings-row]').forEach((row) => {
                    const match = row.getAttribute('data-search').includes(query);

                    row.style.display = match ? '' : 'none';

                    if (match) {
                        anyVisible = true;
                        totalVisible++;
                    }
                });

                section.style.display = anyVisible ? '' : 'none';
            });

            // Show the empty state only when a query matches nothing (never blank).
            const empty = document.querySelector('[data-settings-empty]');

            if (empty) {
                empty.classList.toggle('hidden', ! (query !== '' && totalVisible === 0));
            }
        });
    </script>
@endPushOnce
