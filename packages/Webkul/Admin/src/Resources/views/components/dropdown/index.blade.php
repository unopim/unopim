@props([
    'position' => 'bottom-left',
    'teleport' => false,
])

<v-dropdown
    position="{{ $position }}"
    :teleport="{{ $teleport ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    @isset($toggle)
        {{ $toggle }}

        <template v-slot:toggle>
            {{ $toggle }}
        </template>
    @endisset

    @isset($content)
        <template v-slot:content>
            <div {{ $content->attributes->merge(['class' => 'p-5']) }}>
                {{ $content }}
            </div>
        </template>
    @endisset

    @isset($menu)
        <template v-slot:menu>
            <ul {{ $menu->attributes->merge(['class' => 'py-4']) }}>
                {{ $menu }}
            </ul>
        </template>
    @endisset
</v-dropdown>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dropdown-template"
    >
        <div>
            <div
                class="select-none flex"
                ref="toggleBlock"
                @click="toggle()"
            >
                <slot name="toggle">Toggle</slot>
            </div>

            {{--
                Teleported panels are fixed-positioned against the toggle: an absolute
                panel is cut off by the first scrolling or clipping ancestor, which is
                what happens inside cards, drawers and horizontally scrolling tables.
            --}}
            <teleport
                to="body"
                :disabled="! teleport"
            >
                <transition
                    tag="div"
                    name="dropdown"
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <div
                        class="bg-white dark:bg-cherry-900 shadow-[0px_8px_10px_0px_rgba(0,0,0,0.20),0px_6px_30px_0px_rgba(0,0,0,0.12),0px_16px_24px_0px_rgba(0,0,0,0.14)] rounded w-max"
                        ref="panel"
                        :class="teleport ? 'fixed' : 'absolute z-10'"
                        :style="positionStyles"
                        v-show="isActive"
                    >
                        <slot name="content"></slot>

                        <slot name="menu"></slot>
                    </div>
                </transition>
            </teleport>
        </div>
    </script>

    <script type="module">
        app.component('v-dropdown', {
            template: '#v-dropdown-template',

            props: {
                position: String,

                closeOnClick: {
                    type: Boolean,
                    required: false,
                    default: true
                },

                teleport: {
                    type: Boolean,
                    required: false,
                    default: false
                },
            },

            data() {
                return {
                    toggleBlockWidth: 0,

                    toggleBlockHeight: 0,

                    isActive: false,

                    fixedStyles: {},
                };
            },

            created() {
                window.addEventListener('click', this.handleFocusOut);
            },

            mounted() {
                this.measureToggle();

                if (this.teleport) {
                    this.handleViewportChange = () => this.isActive && this.positionPanel();

                    window.addEventListener('resize', this.handleViewportChange);
                    window.addEventListener('scroll', this.handleViewportChange, true);
                }
            },

            beforeUnmount() {
                window.removeEventListener('click', this.handleFocusOut);

                if (this.teleport) {
                    window.removeEventListener('resize', this.handleViewportChange);
                    window.removeEventListener('scroll', this.handleViewportChange, true);
                }
            },

            computed: {
                positionStyles() {
                    if (this.teleport) {
                        return this.fixedStyles;
                    }

                    switch (this.position) {
                        case 'bottom-left':
                            return [
                                `min-width: ${this.toggleBlockWidth}px`,
                                `top: ${this.toggleBlockHeight}px`,
                                'left: 0',
                            ];

                        case 'bottom-right':
                            return [
                                `min-width: ${this.toggleBlockWidth}px`,
                                `top: ${this.toggleBlockHeight}px`,
                                'right: 0',
                            ];

                        case 'top-left':
                            return [
                                `min-width: ${this.toggleBlockWidth}px`,
                                `bottom: ${this.toggleBlockHeight*2}px`,
                                'left: 0',
                            ];

                        case 'top-right':
                            return [
                                `min-width: ${this.toggleBlockWidth}px`,
                                `bottom: ${this.toggleBlockHeight*2}px`,
                                'right: 0',
                            ];

                        default:
                            return [
                                `min-width: ${this.toggleBlockWidth}px`,
                                `top: ${this.toggleBlockHeight}px`,
                                'left: 0',
                            ];
                    }
                },
            },

            methods: {
                toggle() {
                    this.isActive = ! this.isActive;

                    /**
                     * Re-measure the toggle when opening. Dropdowns mounted inside a hidden
                     * container (e.g. a collapsed filter row or a closed drawer) measure 0 in
                     * mounted(), which collapses the menu to content width and pins it top-left.
                     */
                    if (this.isActive) {
                        this.measureToggle();

                        if (this.teleport) {
                            this.$nextTick(this.positionPanel);
                        }
                    }
                },

                positionPanel() {
                    const toggle = this.$refs.toggleBlock;
                    const panel  = this.$refs.panel;

                    if (! toggle || ! panel) {
                        return;
                    }

                    const margin  = 8;
                    const gap     = 4;
                    const anchor  = toggle.getBoundingClientRect();
                    const width   = panel.offsetWidth;
                    const height  = panel.offsetHeight;
                    const alignsRight = this.position?.endsWith('right');
                    const opensUp     = this.position?.startsWith('top');

                    const left = alignsRight ? anchor.right - width : anchor.left;
                    const above = anchor.top - height - gap;
                    const below = anchor.bottom + gap;

                    /** Flip to whichever side has room, then clamp so the panel stays on screen. */
                    let top = opensUp ? above : below;

                    if (! opensUp && below + height > window.innerHeight - margin && above >= margin) {
                        top = above;
                    }

                    if (opensUp && above < margin && below + height <= window.innerHeight - margin) {
                        top = below;
                    }

                    this.fixedStyles = {
                        zIndex: 10010,
                        minWidth: `${this.toggleBlockWidth}px`,
                        maxHeight: `${window.innerHeight - 2 * margin}px`,
                        overflowY: 'auto',
                        left: `${Math.max(margin, Math.min(left, window.innerWidth - width - margin))}px`,
                        top: `${Math.max(margin, Math.min(top, window.innerHeight - height - margin))}px`,
                    };
                },

                measureToggle() {
                    const block = this.$refs.toggleBlock;

                    if (! block) {
                        return;
                    }

                    if (block.clientWidth) {
                        this.toggleBlockWidth = block.clientWidth;
                    }

                    if (block.clientHeight) {
                        this.toggleBlockHeight = block.clientHeight;
                    }
                },

                handleFocusOut(e) {
                    {{-- A teleported panel is no longer a descendant of the root, so test both nodes. --}}
                    const inToggle = this.$refs.toggleBlock?.contains(e.target);
                    const inPanel  = this.$refs.panel?.contains(e.target);

                    if ((! inToggle && ! inPanel) || (this.closeOnClick && inPanel)) {
                        this.isActive = false;
                    }
                },
            },
        });
    </script>
@endPushOnce
