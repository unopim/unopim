@pushOnce('scripts')

    <script type="text/x-template" id="v-unsaved-changes-template">
        <div ref="root" class="unsaved-root" :class="{ 'unsaved-hideable': hideSaveWhenTracked }">
            {!! view_render_event('unopim.admin.components.form.unsaved_changes.before') !!}

            <slot></slot>

            <teleport to="body">
                <transition name="unsaved-bar">
                    <div
                        v-if="isDirty"
                        class="unsaved-bar fixed bottom-0 z-[999] border-t border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900 shadow-[0_-4px_16px_rgba(0,0,0,0.08)]"
                    >
                        <div class="unsaved-bar-inner flex items-center justify-between gap-3 mx-auto px-4 py-2.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-6 h-6 shrink-0 text-yellow-500"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>

                                <div class="unsaved-bar-text flex flex-col min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">
                                        @lang('admin::app.components.form.unsaved-changes.title')
                                    </p>

                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ subtitle }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                {!! view_render_event('unopim.admin.components.form.unsaved_changes.actions.before') !!}

                                <button type="button" class="secondary-button" @click="discard">
                                    @lang('admin::app.components.form.unsaved-changes.discard')
                                </button>

                                <button type="button" class="primary-button whitespace-nowrap" @click="save">
                                    @lang('admin::app.components.form.unsaved-changes.save')
                                </button>

                                {!! view_render_event('unopim.admin.components.form.unsaved_changes.actions.after') !!}
                            </div>
                        </div>
                    </div>
                </transition>
            </teleport>

            {!! view_render_event('unopim.admin.components.form.unsaved_changes.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-unsaved-changes', {
            template: '#v-unsaved-changes-template',

            props: {
                hideSaveWhenTracked: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    initial: {},
                    elementInitial: null,
                    badgeLabel: "@lang('admin::app.components.form.unsaved-changes.field-badge')",
                    touched: {},
                    dirtyFields: [],
                    sectionCount: 0,
                    debounced: null,
                    beforeUnloadBound: false,
                    onFormEvent: null,
                    onCustomTouch: null,
                    hasTrusted: false,
                    barOpen: false,
                };
            },

            computed: {
                isDirty() {
                    return this.dirtyFields.length > 0;
                },

                subtitle() {
                    if (this.sectionCount > 0) {
                        const key = this.sectionCount === 1
                            ? "@lang('admin::app.components.form.unsaved-changes.section')"
                            : "@lang('admin::app.components.form.unsaved-changes.sections')";

                        return key.replace(':count', this.sectionCount);
                    }

                    const count = this.dirtyFields.length;
                    const key = count === 1
                        ? "@lang('admin::app.components.form.unsaved-changes.field')"
                        : "@lang('admin::app.components.form.unsaved-changes.fields')";

                    return key.replace(':count', count);
                },
            },

            mounted() {
                this.debounced = this.debounce(this.recompute, 80);

                this.onFormEvent = (event) => {
                    if (event.isTrusted && event.target && event.target.closest) {
                        this.hasTrusted = true;

                        const direct = event.target.closest('[name]');

                        if (direct && direct.name) {
                            this.touched[direct.name] = true;
                        }

                        const group = event.target.closest('[data-control-group]');

                        if (group) {
                            group.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
                                if (el.name) {
                                    this.touched[el.name] = true;
                                }
                            });
                        }
                    }

                    this.debounced();
                };

                this.onCustomTouch = (event) => {
                    const base = event.detail && event.detail.name;

                    if (! base) {
                        return;
                    }

                    const matches = (name) => name === base || name.startsWith(base + '[');

                    this.controls().forEach(el => {
                        if (matches(el.name)) {
                            this.touched[el.name] = true;
                        }
                    });

                    Object.keys(this.initial).forEach(name => {
                        if (matches(name)) {
                            this.touched[name] = true;
                        }
                    });

                    this.debounced();
                };

                this.$nextTick(() => {
                    this.snapshot();

                    ['input', 'change', 'click', 'keyup'].forEach(evt => {
                        this.$refs.root.addEventListener(evt, this.onFormEvent, true);
                    });

                    this.$refs.root.addEventListener('unsaved-changes:touch', this.onCustomTouch, true);

                    setTimeout(() => {
                        if (! this.hasTrusted) {
                            this.snapshot();
                            this.recompute();
                        }
                    }, 700);

                    // Physically remove the form's own save button (not just hide it) so the
                    // bar's "Save changes" is the only one. Runs now and once more shortly
                    // after in case the button is rendered slightly late; the CSS rule stays
                    // as a backstop for anything rendered even later.
                    if (this.hideSaveWhenTracked) {
                        this.removeInFormSave();
                        setTimeout(() => this.removeInFormSave(), 400);
                    }
                });

                this.$emitter.on('form-saved', this.onFormSaved);
            },

            beforeUnmount() {
                if (this.$refs.root) {
                    ['input', 'change', 'click', 'keyup'].forEach(evt => {
                        this.$refs.root.removeEventListener(evt, this.onFormEvent, true);
                    });

                    this.$refs.root.removeEventListener('unsaved-changes:touch', this.onCustomTouch, true);
                }

                this.$emitter.off('form-saved', this.onFormSaved);

                this.toggleBeforeUnload(false);
                this.setBarOpen(false);
            },

            methods: {
                controls() {
                    return [...this.$refs.root.querySelectorAll('input[name], select[name], textarea[name]')]
                        .filter(el => ! ['submit', 'button'].includes(el.type))
                        .filter(el => ! ['_token', '_method'].includes(el.name))
                        // Read-only / disabled fields can't be edited by the user, so they
                        // must never count as an unsaved change (e.g. a locked Type select).
                        .filter(el => ! el.disabled && ! el.readOnly);
                },

                // Serialize the form exactly as the browser would submit it. FormData
                // collapses same-name groups (a checkbox + its hidden `0` fallback),
                // omits unchecked boxes, and handles selects/radios/files — so a value
                // that returns to its original truly reads as unchanged.
                serializeForm() {
                    const form = this.$refs.root.querySelector('form');
                    const map = {};

                    if (! form) {
                        return map;
                    }

                    for (const [key, value] of new FormData(form).entries()) {
                        if (key === '_token' || key === '_method') {
                            continue;
                        }

                        const part = (value instanceof File) ? (value.name + ':' + value.size) : String(value);

                        map[key] = (map[key] === undefined) ? part : (map[key] + '' + part);
                    }

                    return map;
                },

                captureElementValue(el) {
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        return { checked: el.checked };
                    }

                    if (el.multiple) {
                        return { selected: [...el.options].map(o => o.selected) };
                    }

                    return { value: el.value };
                },

                snapshot() {
                    this.initial = this.serializeForm();
                    this.touched = {};
                    this.elementInitial = new Map();

                    this.controls().forEach(el => {
                        this.elementInitial.set(el, this.captureElementValue(el));
                    });
                },

                recompute() {
                    const current = this.serializeForm();
                    const dirty = new Set();
                    const names = new Set([...Object.keys(current), ...Object.keys(this.initial)]);

                    names.forEach(name => {
                        if (this.touched[name] && current[name] !== this.initial[name]) {
                            dirty.add(name);
                        }
                    });

                    this.dirtyFields = [...dirty];
                    this.sectionCount = this.countSections(dirty);
                    this.markDirtyGroups(dirty);
                    this.toggleBeforeUnload(this.isDirty);
                    this.setBarOpen(this.isDirty);
                },

                setBarOpen(on) {
                    if (on === this.barOpen) {
                        return;
                    }

                    this.barOpen = on;

                    window.__unsavedBarCount = (window.__unsavedBarCount || 0) + (on ? 1 : -1);

                    if (window.__unsavedBarCount < 0) {
                        window.__unsavedBarCount = 0;
                    }

                    document.body.classList.toggle('unsaved-bar-open', window.__unsavedBarCount > 0);
                },

                countSections(dirtySet) {
                    const sections = new Set();

                    dirtySet.forEach(name => {
                        const el = this.$refs.root.querySelector('[name="' + CSS.escape(name) + '"]');
                        const section = el && el.closest('[data-dirty-section]');

                        if (section) {
                            sections.add(section);
                        }
                    });

                    return sections.size;
                },

                markDirtyGroups(dirtySet) {
                    this.$refs.root.querySelectorAll('[data-control-group].unsaved-dirty').forEach(g => g.classList.remove('unsaved-dirty'));
                    this.$refs.root.querySelectorAll('[data-unsaved-injected]').forEach(b => b.remove());

                    dirtySet.forEach(name => {
                        const el = this.$refs.root.querySelector('[name="' + CSS.escape(name) + '"]');
                        const group = el && el.closest('[data-control-group]');

                        if (! group) {
                            return;
                        }

                        group.classList.add('unsaved-dirty');

                        // Fields whose label comes from x-admin::form.control-group.label
                        // already carry a badge (revealed by CSS). Checkboxes/toggles use a
                        // plain <label>, so inject a badge into the group for those.
                        if (! group.querySelector('.unsaved-badge')) {
                            const badge = document.createElement('span');

                            badge.className = 'unsaved-badge';
                            badge.setAttribute('data-unsaved-injected', '');
                            badge.textContent = this.badgeLabel;

                            group.appendChild(badge);
                        }
                    });
                },

                toggleBeforeUnload(on) {
                    if (on && ! this.beforeUnloadBound) {
                        window.addEventListener('beforeunload', this.onBeforeUnload);
                        this.beforeUnloadBound = true;
                    } else if (! on && this.beforeUnloadBound) {
                        window.removeEventListener('beforeunload', this.onBeforeUnload);
                        this.beforeUnloadBound = false;
                    }
                },

                onBeforeUnload(event) {
                    if (window.__unsavedNavBypass) {
                        return;
                    }

                    event.preventDefault();
                    event.returnValue = '';

                    return '';
                },

                discard() {
                    // Ask first — discarding is destructive and one accidental click would
                    // otherwise wipe every unsaved edit with no undo. Reuse UnoPim's shared
                    // confirm modal (open-confirm-modal) so it looks/behaves like every other
                    // confirmation in the app.
                    this.$emitter.emit('open-confirm-modal', {
                        title: "@lang('admin::app.components.form.unsaved-changes.discard-title')",
                        message: "@lang('admin::app.components.form.unsaved-changes.discard-message')",
                        options: {
                            btnAgree: "@lang('admin::app.components.form.unsaved-changes.discard')",
                            btnDisagree: "@lang('admin::app.components.form.unsaved-changes.cancel')",
                            btnAgreeClass: 'danger-button',
                            btnDisagreeClass: 'transparent-button',
                        },
                        agree: () => this.performDiscard(),
                    });
                },

                performDiscard() {
                    this.controls().forEach(el => {
                        const init = this.elementInitial && this.elementInitial.get(el);

                        if (! init) {
                            return;
                        }

                        if ('checked' in init) {
                            el.checked = init.checked;
                        } else if ('selected' in init) {
                            [...el.options].forEach((o, i) => { o.selected = !! init.selected[i]; });
                        } else {
                            el.value = init.value;
                        }

                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    this.touched = {};
                    this.recompute();
                },

                onFormSaved() {
                    this.hasTrusted = false;
                    this.touched = {};

                    this.snapshot();
                    this.recompute();
                },

                save() {
                    this.toggleBeforeUnload(false);

                    const form = this.$refs.root.querySelector('form');

                    if (! form) {
                        return;
                    }

                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                },

                removeInFormSave() {
                    const form = this.$refs.root.querySelector('form');

                    if (! form) {
                        return;
                    }

                    // Action-only forms (no editable fields — e.g. an "Export Now" / "Import Now"
                    // PUT trigger) can never turn the bar dirty, so their submit button is the ONLY
                    // way to act. Never strip it; the bar would leave the user with no button at all.
                    // A media widget counts as content: it has no named field but marks the form
                    // dirty via `unsaved-changes:touch`, so a media-only form still uses the bar.
                    if (this.controls().length === 0 && ! form.querySelector('[data-media-control]')) {
                        return;
                    }

                    form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(btn => {
                        // Keep submit buttons that belong to a modal / dialog inside this
                        // form — they have their own submit and the bar can't handle them.
                        if (btn.closest('[data-unsaved-ignore]')) {
                            return;
                        }

                        // Remove outright: a hidden submit button still acts as the
                        // form's implicit submit-on-Enter, so it must be gone (not just
                        // hidden). The SPA re-renders the form on each visit, so it comes
                        // back cleanly and is re-removed by mount.
                        btn.remove();
                    });
                },

                debounce(fn, ms) {
                    let timer;

                    return (...args) => {
                        clearTimeout(timer);
                        timer = setTimeout(() => fn.apply(this, args), ms);
                    };
                },
            },
        });

        /*
         * Internal-navigation guard (installed once). While any tracked form is dirty,
         * clicking an in-app link (sidebar menu, Edit, breadcrumbs) opens a styled
         * confirm instead of silently reloading. Fail-open: new-tab, download, external,
         * hash, and non-link clicks navigate normally. Native beforeunload still covers
         * tab-close / hard reload.
         */
        if (! window.__unsavedNavGuardInstalled) {
            window.__unsavedNavGuardInstalled = true;

            const unsavedNavStrings = {
                title:   "@lang('admin::app.components.form.unsaved-changes.title')",
                message: "@lang('admin::app.components.form.unsaved-changes.leave-message')",
                stay:    "@lang('admin::app.components.form.unsaved-changes.stay')",
                leave:   "@lang('admin::app.components.form.unsaved-changes.leave')",
            };

            const showUnsavedNavModal = (href) => {
                // Reuse UnoPim's shared confirm modal so the leave-page prompt matches
                // every other confirmation in the app.
                window.app.config.globalProperties.$emitter.emit('open-confirm-modal', {
                    title: unsavedNavStrings.title,
                    message: unsavedNavStrings.message,
                    options: {
                        btnAgree: unsavedNavStrings.leave,
                        btnDisagree: unsavedNavStrings.stay,
                        btnAgreeClass: 'primary-button',
                        btnDisagreeClass: 'transparent-button',
                    },
                    agree: () => {
                        window.__unsavedNavBypass = true;
                        window.location.href = href;
                    },
                });
            };

            document.addEventListener('click', (event) => {
                if (! (window.__unsavedBarCount > 0)) {
                    return;
                }

                const link = event.target.closest ? event.target.closest('a[href]') : null;

                if (! link) {
                    return;
                }

                const raw = link.getAttribute('href') || '';

                if (! raw || raw[0] === '#' || /^(javascript|mailto|tel):/i.test(raw)) {
                    return;
                }

                if ((link.target && link.target !== '_self') || link.hasAttribute('download')) {
                    return;
                }

                if (link.closest('.unsaved-bar') || link.closest('#unsaved-nav-modal')) {
                    return;
                }

                let url;

                try {
                    url = new URL(link.href, window.location.href);
                } catch (e) {
                    return;
                }

                if (url.origin !== window.location.origin || url.href === window.location.href) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                showUnsavedNavModal(url.href);
            }, true);
        }
    </script>
@endPushOnce
