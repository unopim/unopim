        <script type="module">
            app.component('v-job-tracker', {
                template: '#v-job-tracker-template',

                props: [
                    'initialJobTrack',
                    'initialJobInstance',
                    'initialStats',
                    'initialSummary',
                    'initialIsValid',
                    'endpoints',
                    'messages',
                ],

                data() {
                    return {
                        importResource: this.initialJobTrack,
                        jobInstance: this.initialJobInstance,
                        isValid: this.initialIsValid,
                        summary: this.initialSummary,
                        stats: this.initialStats,

                        elapsedSeconds: 0,
                        clockInterval: null,
                        pollTimeout: null,
                        workStartedAt: null,
                        isActionInProgress: false,
                    };
                },

                mounted() {
                    this.getStats();
                },

                beforeUnmount() {
                    this.stopClock();

                    if (this.pollTimeout) {
                        clearTimeout(this.pollTimeout);
                        this.pollTimeout = null;
                    }
                },

                methods: {
                    toBoolean(value) {
                        if (typeof value === 'boolean') {
                            return value;
                        }

                        if (typeof value === 'number') {
                            return value === 1;
                        }

                        if (value === null || value === undefined) {
                            return false;
                        }

                        return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
                    },

                    formatDuration(seconds) {
                        if (seconds < 60) return seconds + 's';
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        if (m < 60) return m + 'm ' + (s > 0 ? s + 's' : '');
                        const h = Math.floor(m / 60);
                        return h + 'h ' + (m % 60) + 'm';
                    },

                    formattedElapsed() {
                        return this.formatDuration(this.elapsedSeconds);
                    },

                    formattedETA() {
                        const progress = parseFloat(this.stats.progress);
                        if (!progress || progress <= 0 || progress >= 100 || !this.workStartedAt) return '—';
                        const workElapsed = (Date.now() - this.workStartedAt) / 1000;
                        if (workElapsed < 2) return '—';
                        const remaining = (workElapsed / progress) * (100 - progress);
                        return this.formatDuration(Math.floor(remaining));
                    },

                    totalDuration() {
                        if (this.importResource.started_at && this.importResource.completed_at) {
                            const start = new Date(this.importResource.started_at).getTime();
                            const end = new Date(this.importResource.completed_at).getTime();
                            return this.formatDuration(Math.floor((end - start) / 1000));
                        }
                        return this.formatDuration(this.elapsedSeconds);
                    },

                    startClock() {
                        if (this.clockInterval || !this.importResource.started_at) return;
                        const startTime = new Date(this.importResource.started_at).getTime();
                        this.elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                        this.clockInterval = setInterval(() => {
                            this.elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                        }, 1000);
                    },

                    stopClock() {
                        if (this.clockInterval) {
                            clearInterval(this.clockInterval);
                            this.clockInterval = null;
                        }
                    },

                    pauseImport() {
                        this.isActionInProgress = true;
                        this.$axios.post(this.endpoints.pause)
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'warning', message: response.data.message });
                                this.getStats();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message || this.messages.pauseFailed });
                            })
                            .finally(() => {
                                this.isActionInProgress = false;
                            });
                    },

                    resumeImport() {
                        this.isActionInProgress = true;
                        this.$axios.post(this.endpoints.resume)
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                this.getStats();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message || this.messages.resumeFailed });
                            })
                            .finally(() => {
                                this.isActionInProgress = false;
                            });
                    },

                    cancelImport() {
                        this.$emitter.emit('open-confirm-modal', {
                            title: '@lang("admin::app.settings.data-transfer.tracker.cancel")',
                            message: '@lang("admin::app.settings.data-transfer.tracker.cancel-confirm")',
                            options: {
                                btnDisagree: '@lang("admin::app.settings.data-transfer.tracker.cancel")',
                                btnAgree: '@lang("admin::app.components.modal.confirm.agree-btn")',
                                btnAgreeClass: 'danger-button',
                                btnDisagreeClass: 'transparent-button',
                            },
                            agree: () => {
                                this.isActionInProgress = true;

                                this.$axios.post(this.endpoints.cancel)
                                    .then((response) => {
                                        this.$emitter.emit('add-flash', { type: 'warning', message: response.data.message });
                                        this.getStats();
                                    })
                                    .catch(error => {
                                        this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message || this.messages.cancelFailed });
                                    })
                                    .finally(() => {
                                        this.isActionInProgress = false;
                                    });
                            },
                        });
                    },

                    getStats() {
                        let state = 'processed';

                        if (this.importResource.state == 'linking')  {
                            state = 'linked';
                        } else if (this.importResource.state == 'indexing') {
                            state = 'indexed';
                        }

                        this.$axios.get(this.endpoints.stats + '/' + state)
                            .then((response) => {
                                this.importResource = response.data.import;
                                this.stats = response.data.stats;
                                this.isValid = response.data.isValid;
                                this.summary = response.data.summary;
                                this.jobInstance = response.data.jobInstance;

                                const activeStates = ['validating', 'processing', 'processed', 'linking', 'indexing'];
                                if (activeStates.includes(this.importResource.state)) {
                                    this.startClock();
                                    if (parseFloat(this.stats.progress) > 0 && this.workStartedAt === null) {
                                        this.workStartedAt = Date.now();
                                    }
                                } else {
                                    this.stopClock();
                                }

                                const pollingStates = ['pending', 'validating', 'validated', 'processing', 'processed', 'linking', 'indexing', 'paused'];
                                if (pollingStates.includes(this.importResource.state)) {
                                    this.pollTimeout = setTimeout(() => {
                                        this.getStats();
                                    }, 1000);
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message });
                            });
                    }
                }
            })
        </script>
