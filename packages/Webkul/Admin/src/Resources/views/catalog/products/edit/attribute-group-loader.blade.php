@props(['productId', 'nextGroupId'])

<v-product-attribute-group-loader
    endpoint="{{ route('admin.catalog.products.attribute_group_fields', ['id' => $productId, 'groupId' => '__group__']) }}"
    :next-group-id="{{ (int) $nextGroupId }}"
></v-product-attribute-group-loader>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-attribute-group-loader-template">
        <div>
            <div ref="target" class="h-px"></div>

            <x-admin::shimmer.accordion v-if="loading" />

            <button
                v-if="failed && ! loading"
                type="button"
                class="secondary-button self-start"
                @click="load"
            >
                @lang('admin::app.catalog.products.edit.attribute-groups.retry')
            </button>
        </div>
    </script>

    <script type="module">
        app.component('v-product-attribute-group-loader', {
            template: '#v-product-attribute-group-loader-template',

            props: {
                endpoint: {
                    type: String,
                    required: true,
                },

                nextGroupId: {
                    type: Number,
                    default: null,
                },
            },

            data() {
                return {
                    pending: this.nextGroupId,
                    loading: false,
                    failed: false,
                    observer: null,
                };
            },

            mounted() {
                this.$emitter.on('attribute-group:reveal-field', this.revealField);

                this.observer = new IntersectionObserver(
                    (entries) => {
                        if (entries.some((entry) => entry.isIntersecting)) {
                            this.load();
                        }
                    },
                    { rootMargin: '600px' }
                );

                this.observer.observe(this.$refs.target);
            },

            beforeUnmount() {
                this.observer?.disconnect();
            },

            methods: {
                /**
                 * Pull in one specific group so a field the server rejected can be
                 * shown, even when the editor never scrolled that far.
                 */
                revealField({ name, groupId, message }) {
                    if (! groupId || this.loading) {
                        return;
                    }

                    this.loading = true;

                    this.$axios
                        .get(this.endpoint.replace('__group__', groupId))
                        .then(({ data }) => this.append(data, false))
                        .then(() => {
                            const field = document.querySelector('[name="' + CSS.escape(name) + '"]');

                            window.revealInvalidField(field, message);
                        })
                        .catch(() => {
                            this.failed = true;
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                load() {
                    if (this.loading || ! this.pending) {
                        return;
                    }

                    this.loading = true;
                    this.failed = false;

                    this.$axios
                        .get(this.endpoint.replace('__group__', this.pending))
                        .then(({ data }) => this.append(data))
                        .catch(() => {
                            this.failed = true;
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                async append(data, advanceChain = true) {
                    if (advanceChain) {
                        this.pending = data.nextGroupId;
                    }

                    // A group pulled in out of order must not be appended twice
                    // when the scroll chain later reaches it.
                    if (document.querySelector(`[data-attribute-group-id="${data.groupId}"]`)) {
                        return;
                    }

                    const holder = document.createElement('div');

                    holder.className = 'flex flex-col gap-2';
                    holder.innerHTML = data.html;

                    this.$el.parentNode.insertBefore(holder, this.$el);

                    /**
                     * The page's own Vue app is already mounted, so markup added
                     * afterwards is never compiled by it. Each appended group gets
                     * its own app instance built from the same factory, and the
                     * fragment's component registrations are re-run against that
                     * instance before it mounts — `window.app` is what those
                     * registration scripts assign to.
                     */
                    const parentApp = window.app;

                    const groupApp = window.createAdminApp();

                    await Promise.all(
                        [...holder.querySelectorAll('script[type="module"]')].map((script) => {
                            const url = URL.createObjectURL(
                                new Blob([script.textContent], { type: 'text/javascript' })
                            );

                            return import(url).finally(() => URL.revokeObjectURL(url));
                        })
                    );

                    groupApp.mount(holder);

                    window.app = parentApp;

                    /**
                     * The observer only reports transitions. Collapsed groups add
                     * almost no height, so the sentinel can stay on screen and
                     * never fire again; re-observing re-delivers its current state
                     * and keeps the chain going until the viewport is filled.
                     */
                    if (this.pending) {
                        this.observer.unobserve(this.$refs.target);

                        this.$nextTick(() => this.observer.observe(this.$refs.target));
                    }

                    /**
                     * Controls that join the form after its baseline snapshot are
                     * invisible to the unsaved-changes bar until it re-reads them.
                     */
                    holder.dispatchEvent(new CustomEvent('unsaved-changes:sync', { bubbles: true }));

                    if (! this.pending) {
                        this.observer?.disconnect();
                    }
                },
            },
        });
    </script>
@endPushOnce
