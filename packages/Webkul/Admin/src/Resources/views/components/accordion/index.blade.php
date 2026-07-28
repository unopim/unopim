@props([
    'isActive'   => true,
    'persistKey' => null,
])

<div data-dirty-section {{ $attributes->merge(['class' => 'bg-white dark:bg-cherry-900 rounded box-shadow']) }}>
    <v-accordion
        is-active="{{ $isActive }}"
        @if ($persistKey) persist-key="{{ $persistKey }}" @endif
        {{ $attributes }}
    >
        <x-admin::shimmer.accordion class="w-[360px] h-[271px]" />

        @isset($header)
            <template v-slot:header="{ toggle, toggleFromHeader, isOpen }">
                <div
                    {{ $header->attributes->merge(['class' => 'flex items-center justify-between p-1.5 px-4 cursor-pointer select-none']) }}
                    role="button"
                    tabindex="0"
                    :aria-expanded="isOpen ? 'true' : 'false'"
                    @click="toggleFromHeader"
                    @keydown.enter.prevent="toggle"
                    @keydown.space.prevent="toggle"
                >
                    {{ $header }}

                    <span
                        :class="`text-2xl p-1.5 rounded-md cursor-pointer transition-all hover:bg-primary-50 dark:hover:bg-cherry-800 ${isOpen ? 'icon-chevron-up' : 'icon-chevron-down'}`"
                        :title="isOpen
                            ? '@lang('admin::app.components.accordion.collapse')'
                            : '@lang('admin::app.components.accordion.expand')'"
                        @click.stop="toggle"
                    ></span>
                </div>
            </template>
        @endisset

        @isset($content)
            <template v-slot:content="{ isOpen }">
                <div
                    {{ $content->attributes->merge(['class' => 'px-4 pb-4']) }}
                    v-show="isOpen"
                >
                    {{ $content }}
                </div>
            </template>
        @endisset
    </v-accordion>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-accordion-template"
    >
        <div>
            <slot
                name="header"
                :toggle="toggle"
                :toggleFromHeader="toggleFromHeader"
                :isOpen="isOpen"
            >
                Default Header
            </slot>

            <slot
                name="content"
                :isOpen="isOpen"
            >
                Default Content
            </slot>
        </div>
    </script>

    <script type="module">
        app.component('v-accordion', {
            template: '#v-accordion-template',

            props: [
                'isActive',
                'persistKey',
            ],

            data() {
                return {
                    isOpen: this.storedState() ?? this.isActive,
                };
            },

            mounted() {
                this.$el.addEventListener('accordion:open', this.open);
            },

            beforeUnmount() {
                this.$el.removeEventListener('accordion:open', this.open);
            },

            methods: {
                /**
                 * The whole header row toggles, but a header may carry its own
                 * controls (buttons, links, form fields); a click on those must
                 * do only what they do.
                 */
                toggleFromHeader(event) {
                    if (event.target.closest('a, button, input, select, textarea, label, [data-no-toggle]')) {
                        return;
                    }

                    this.toggle();
                },

                open() {
                    if (this.isOpen) {
                        return;
                    }

                    this.isOpen = true;

                    this.store();
                },

                toggle() {
                    this.isOpen = ! this.isOpen;

                    this.store();

                    this.$emit('toggle', { isActive: this.isOpen });
                },

                storageKey() {
                    return this.persistKey ? `unopim.accordion.${this.persistKey}` : null;
                },

                /**
                 * Only an explicitly stored `false` overrides the default: an
                 * absent entry must not force a panel open that was rendered
                 * closed, and vice versa.
                 */
                storedState() {
                    const key = this.storageKey();

                    if (! key) {
                        return null;
                    }

                    try {
                        const stored = window.localStorage.getItem(key);

                        return stored === null ? null : stored === '1';
                    } catch (error) {
                        return null;
                    }
                },

                store() {
                    const key = this.storageKey();

                    if (! key) {
                        return;
                    }

                    try {
                        window.localStorage.setItem(key, this.isOpen ? '1' : '0');
                    } catch (error) {
                        //
                    }
                },
            },
        });
    </script>
@endPushOnce
