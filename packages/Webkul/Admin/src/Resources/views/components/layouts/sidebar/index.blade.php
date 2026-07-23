<div id="unopim-sidebar" class="flex flex-col shrink-0 h-full bg-white dark:bg-cherry-700 w-[270px] shadow-[0px_8px_10px_0px_rgba(0,_0,_0,_0.2)] z-[1000] max-lg:hidden transition-all duration-300 group-[.sidebar-collapsed]/container:w-[70px]">
    <div id="unopim-sidebar-scroll" class="flex-1 min-h-0 overflow-auto journal-scroll group-[.sidebar-collapsed]/container:overflow-visible">
        <nav class="grid gap-2 mt-2 w-full">
            @foreach ($menu->items as $menuItem)
                <div
                    class="px-4 group/item {{ $menu->getActive($menuItem) ? 'active' : 'inactive' }}"
                    data-menu-item
                >
                    <a
                        href="{{ $menuItem['url'] }}"
                        class="flex gap-2.5 p-1.5 items-center cursor-pointer hover:rounded-lg {{ $menu->getActive($menuItem) == 'active' ? 'bg-unopim-primary-muted bg-primary-100 dark:bg-unopim-primary-900/40 rounded-lg' : ' hover:bg-unopim-primary-soft hover:bg-primary-50 hover:dark:bg-cherry-800' }} peer"
                    >
                        <span class="{{ $menuItem['icon'] }} text-2xl {{ $menu->getActive($menuItem) ? 'text-unopim-primary text-primary-700 dark:text-primary-400' : ''}}"></span>

                        <p class="font-semibold whitespace-nowrap group-[.sidebar-collapsed]/container:hidden {{ $menu->getActive($menuItem) ? 'text-unopim-primary text-primary-700 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300'}}">
                            @lang($menuItem['name'])
                        </p>
                    </a>

                    @if (count($menuItem['children']))
                        <div class="{{ $menu->getActive($menuItem) ? '!grid' : '' }} hidden min-w-[180px] ltr:pl-10 rtl:pr-10 pb-2 rounded-b-lg z-[100] overflow-hidden group-[.sidebar-collapsed]/container:!hidden group-[.sidebar-collapsed]/container:fixed group-[.sidebar-collapsed]/container:ltr:!left-[70px] group-[.sidebar-collapsed]/container:rtl:!right-[70px] group-[.sidebar-collapsed]/container:p-[0] group-[.sidebar-collapsed]/container:bg-white dark:group-[.sidebar-collapsed]/container:bg-cherry-700 group-[.sidebar-collapsed]/container:border group-[.sidebar-collapsed]/container:ltr:rounded-r-lg group-[.sidebar-collapsed]/container:rtl:rounded-l-lg group-[.sidebar-collapsed]/container:border-gray-300 group-[.sidebar-collapsed]/container:dark:border-cherry-800 group-[.sidebar-collapsed]/container:rounded-none group-[.sidebar-collapsed]/container:ltr:shadow-[34px_10px_14px_rgba(0,0,0,0.01),19px_6px_12px_rgba(0,0,0,0.03),9px_3px_9px_rgba(0,0,0,0.04),2px_1px_5px_rgba(0,0,0,0.05),0px_0px_0px_rgba(0,0,0,0.05)] group-[.sidebar-collapsed]/container:rtl:shadow-[-34px_10px_14px_rgba(0,0,0,0.01),-19px_6px_12px_rgba(0,0,0,0.03),-9px_3px_9px_rgba(0,0,0,0.04),-2px_1px_5px_rgba(0,0,0,0.05),-0px_0px_0px_rgba(0,0,0,0.05)] group-[.sidebar-collapsed]/container:group-hover/item:!grid group-[.inactive]/item:hidden group-[.inactive]/item:fixed group-[.inactive]/item:ltr:left-[270px] group-[.inactive]/item:rtl:right-[270px] group-[.inactive]/item:p-[0] group-[.inactive]/item:bg-white dark:group-[.inactive]/item:bg-cherry-700 group-[.inactive]/item:border group-[.inactive]/item:ltr:rounded-r-lg group-[.inactive]/item:rtl:rounded-l-lg group-[.inactive]/item:border-gray-300 group-[.inactive]/item:dark:border-cherry-800 group-[.inactive]/item:rounded-none group-[.inactive]/item:ltr:shadow-[34px_10px_14px_rgba(0,0,0,0.01),19px_6px_12px_rgba(0,0,0,0.03),9px_3px_9px_rgba(0,0,0,0.04),2px_1px_5px_rgba(0,0,0,0.05),0px_0px_0px_rgba(0,0,0,0.05)] group-[.inactive]/item:rtl:shadow-[-34px_10px_14px_rgba(0,0,0,0.01),-19px_6px_12px_rgba(0,0,0,0.03),-9px_3px_9px_rgba(0,0,0,0.04),-2px_1px_5px_rgba(0,0,0,0.05),-0px_0px_0px_rgba(0,0,0,0.05)] group-[.inactive]/item:group-hover/item:!grid" data-submenu>
                            @foreach ($menuItem['children'] as $subMenuItem)
                                <a
                                    href="{{ $subMenuItem['url'] }}"
                                    class="text-sm {{ $menu->getActive($subMenuItem) ? 'text-unopim-primary text-primary-700 dark:text-primary-400':'text-gray-600 dark:text-gray-300' }} whitespace-nowrap py-1 group-[.sidebar-collapsed]/container:px-5 group-[.sidebar-collapsed]/container:py-2.5 group-[.inactive]/item:px-5 group-[.inactive]/item:py-2.5 hover:text-unopim-primary hover:text-primary-700 dark:hover:text-primary-400"
                                >
                                    @lang($subMenuItem['name'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>
    </div>

    <v-sidebar-collapse></v-sidebar-collapse>
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-sidebar-collapse-template">
        <div
            class="bg-white dark:bg-cherry-700 shrink-0 w-full px-4 hover:bg-unopim-primary-soft hover:bg-primary-50 dark:hover:bg-cherry-800 border-t border-gray-200 dark:border-cherry-800  transition-all duration-300 cursor-pointer"
            @click="toggle"
        >
            <div class="flex gap-2.5 p-1.5 items-center">
                <span
                    class="icon-collapse transition-all text-2xl"
                    :class="[isCollapsed ? 'ltr:rotate-[180deg] rtl:rotate-[0]' : 'ltr:rotate-[0] rtl:rotate-[180deg]']"
                ></span>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-sidebar-collapse', {
            template: '#v-sidebar-collapse-template',

            data() {
                return {
                    isCollapsed: {{ request()->cookie('sidebar_collapsed') ?? 1 }},
                }
            },

            methods: {
                toggle() {
                    this.isCollapsed = this.isCollapsed ? 0 : 1;

                    const expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'sidebar_collapsed=' + this.isCollapsed + '; path=/; expires=' + expiryDate.toGMTString();

                    this.$root.$refs.appLayout.classList.toggle('sidebar-collapsed', Boolean(this.isCollapsed));
                },
            },
        });
    </script>

    <script>
        (() => {
            /**
             * Hover-intent for the detached fly-out submenus.
             *
             * The trigger row is far shorter than the fly-out, so moving the pointer
             * diagonally onto a lower sub-item crosses a strip where neither element
             * is hovered; a pure `group-hover` submenu closes before it can be clicked.
             * Holding it open for a short grace period bridges that strip.
             *
             * The collapsed/inactive variants hide the fly-out with `display:none !important`,
             * so the grace period must be forced with `!important` too, otherwise the
             * inline style loses and the grace period silently does nothing.
             */
            const SUBMENU_HIDE_DELAY = 400;

            /**
             * Switching to another parent is deferred: the diagonal path from a trigger
             * row down to a low sub-item sweeps across the icons below it, and swapping
             * fly-outs on contact would drop a new panel over the item being aimed at.
             */
            const SUBMENU_SWITCH_DELAY = 300;

            const VIEWPORT_MARGIN = 8;

            const MENU_ITEM_SELECTOR = '#unopim-sidebar [data-menu-item]';

            const hideTimers = new WeakMap();

            let openSubMenu = null;

            let switchTimer = null;

            const isDetached = (subMenu) => getComputedStyle(subMenu).position === 'fixed';

            const positionSubMenu = (menuItem, subMenu) => {
                if (! isDetached(subMenu)) {
                    subMenu.style.removeProperty('top');

                    return;
                }

                const triggerTop = menuItem.getBoundingClientRect().top;

                const highestTop = window.innerHeight - subMenu.offsetHeight - VIEWPORT_MARGIN;

                subMenu.style.top = `${Math.max(VIEWPORT_MARGIN, Math.min(triggerTop, highestTop))}px`;
            };

            const close = (subMenu) => {
                clearTimeout(hideTimers.get(subMenu));

                subMenu.style.removeProperty('display');
                subMenu.style.removeProperty('top');

                if (openSubMenu === subMenu) {
                    openSubMenu = null;
                }
            };

            const open = (menuItem) => {
                const subMenu = menuItem.querySelector('[data-submenu]');

                if (! subMenu) {
                    return;
                }

                if (openSubMenu && openSubMenu !== subMenu) {
                    close(openSubMenu);
                }

                clearTimeout(hideTimers.get(subMenu));

                // Reveal first: a hidden element measures 0px, which would defeat the clamp.
                subMenu.style.setProperty('display', 'grid', 'important');

                positionSubMenu(menuItem, subMenu);

                openSubMenu = subMenu;
            };

            const scheduleClose = (subMenu) => {
                clearTimeout(hideTimers.get(subMenu));

                hideTimers.set(subMenu, setTimeout(() => close(subMenu), SUBMENU_HIDE_DELAY));
            };

            const requestOpen = (menuItem) => {
                clearTimeout(switchTimer);

                const subMenu = menuItem.querySelector('[data-submenu]');

                // Pointing at the fly-out that is already up, or at the first one: no wait.
                if (! openSubMenu || ! subMenu || openSubMenu === subMenu) {
                    open(menuItem);

                    return;
                }

                // Hold the current fly-out until the swap lands, otherwise the pointer
                // crosses a stretch where neither panel is on screen.
                clearTimeout(hideTimers.get(openSubMenu));

                switchTimer = setTimeout(() => open(menuItem), SUBMENU_SWITCH_DELAY);
            };

            /**
             * From here the script owns fly-out visibility. The CSS `group-hover` reveal
             * is instant, so a second fly-out would pop up over one still held open by its
             * grace period. Injecting the override from JS keeps the CSS-only behaviour
             * intact when the script does not run, and the `#id` beats the utility classes
             * while the inline `!important` used to open a fly-out still beats this.
             */
            const style = document.createElement('style');

            style.textContent = `
                .sidebar-collapsed #unopim-sidebar [data-submenu],
                #unopim-sidebar [data-menu-item].inactive [data-submenu] {
                    display: none !important;
                }
            `;

            document.head.appendChild(style);

            document.addEventListener('mouseover', (event) => {
                const menuItem = event.target.closest?.(MENU_ITEM_SELECTOR);

                if (menuItem) {
                    requestOpen(menuItem);
                }
            });

            document.addEventListener('mouseout', (event) => {
                const menuItem = event.target.closest?.(MENU_ITEM_SELECTOR);

                // The fly-out is a descendant of the trigger, so moving onto it is not a leave.
                if (! menuItem || (event.relatedTarget && menuItem.contains(event.relatedTarget))) {
                    return;
                }

                // A pending switch is abandoned once the pointer leaves, so the panel
                // that is actually on screen is the one that has to go.
                clearTimeout(switchTimer);

                if (openSubMenu) {
                    scheduleClose(openSubMenu);
                }
            });

            document.addEventListener('focusin', (event) => {
                const menuItem = event.target.closest?.(MENU_ITEM_SELECTOR);

                if (menuItem) {
                    open(menuItem);
                } else if (openSubMenu) {
                    close(openSubMenu);
                }
            });

            const repositionOpenSubMenu = () => {
                if (! openSubMenu) {
                    return;
                }

                positionSubMenu(openSubMenu.closest('[data-menu-item]'), openSubMenu);
            };

            window.addEventListener('resize', repositionOpenSubMenu);
            document.addEventListener('scroll', repositionOpenSubMenu, { capture: true, passive: true });
        })();
    </script>
@endpushOnce
