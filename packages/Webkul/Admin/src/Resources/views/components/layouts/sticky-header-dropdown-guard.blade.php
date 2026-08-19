@pushOnce('scripts', 'sticky-header-dropdown-guard')
    <script>
        (() => {
            /**
             * The pinned header outranks an open dropdown, so a panel reaching into
             * its band is not merely hidden: the header takes the clicks there too.
             * Keep the panel out of that band instead -- scroll the field down where
             * the page still can, and cap the panel where it cannot.
             */
            const GAP = 8;

            const MIN_PANEL_HEIGHT = 96;

            const PANEL_SELECTOR = '.multiselect__content-wrapper';

            const headerLimit = () => {
                const header = document.querySelector('.js-sticky-header');

                return header ? header.getBoundingClientRect().bottom + GAP : 0;
            };

            const fit = (multiselect) => {
                const panel = multiselect.querySelector(PANEL_SELECTOR);

                if (! panel) {
                    return;
                }

                panel.style.maxHeight = '';

                const limit = headerLimit();

                if (panel.getBoundingClientRect().top >= limit) {
                    return;
                }

                const scroller = multiselect.closest('#main-content');

                if (scroller) {
                    const overlap = limit - panel.getBoundingClientRect().top;

                    scroller.scrollTop -= Math.min(overlap, scroller.scrollTop);
                }

                const rect = panel.getBoundingClientRect();

                if (rect.top < limit) {
                    panel.style.maxHeight = Math.max(MIN_PANEL_HEIGHT, rect.bottom - limit) + 'px';
                }
            };

            const release = (multiselect) => {
                const panel = multiselect.querySelector(PANEL_SELECTOR);

                if (panel) {
                    panel.style.maxHeight = '';
                }
            };

            new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    const target = mutation.target;

                    if (! target.classList?.contains('multiselect')) {
                        return;
                    }

                    target.classList.contains('multiselect--active')
                        ? requestAnimationFrame(() => fit(target))
                        : release(target);
                });
            }).observe(document.body, {
                subtree: true,
                attributes: true,
                attributeFilter: ['class'],
            });
        })();
    </script>
@endPushOnce
