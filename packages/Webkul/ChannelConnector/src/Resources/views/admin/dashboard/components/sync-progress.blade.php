<div
    x-data="syncProgress({{ $job->id }}, '{{ $job->status }}', {{ $job->total_products ?? 0 }}, {{ $job->synced_products ?? 0 }}, {{ $job->failed_products ?? 0 }})"
    x-init="init()"
>
    {{-- Progress Bar --}}
    <div class="mb-2">
        <div class="h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div class="flex h-4">
                <div
                    class="h-4 bg-green-500 transition-all duration-500"
                    :style="'width: ' + successPercent + '%'"
                ></div>
                <div
                    class="h-4 bg-red-500 transition-all duration-500"
                    :style="'width: ' + failedPercent + '%'"
                ></div>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="flex items-center justify-between text-sm">
        <div class="flex items-center gap-4">
            <span class="text-gray-600 dark:text-gray-400">
                <span x-text="processedCount"></span> / <span x-text="totalProducts"></span>
                @lang('channel_connector::app.dashboard.progress.products-processed')
            </span>

            <span class="text-green-600 dark:text-green-400">
                <span x-text="syncedProducts"></span> @lang('channel_connector::app.dashboard.progress.synced')
            </span>

            <span class="text-red-600 dark:text-red-400">
                <span x-text="failedProducts"></span> @lang('channel_connector::app.dashboard.progress.failed')
            </span>
        </div>

        <div class="flex items-center gap-4">
            <span x-show="isActive" class="text-gray-500 dark:text-gray-400" x-text="overallPercent + '%'"></span>

            <span x-show="isActive && eta !== null" class="text-gray-500 dark:text-gray-400">
                @lang('channel_connector::app.dashboard.progress.eta'): <span x-text="etaFormatted"></span>
            </span>

            <span x-show="isActive" class="flex items-center gap-1">
                <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
                <span class="text-xs text-blue-600 dark:text-blue-400">@lang('channel_connector::app.dashboard.progress.polling')</span>
            </span>
        </div>
    </div>
</div>

<script>
    function syncProgress(jobId, initialStatus, totalProducts, syncedProducts, failedProducts) {
        return {
            jobId: jobId,
            status: initialStatus,
            totalProducts: totalProducts,
            syncedProducts: syncedProducts,
            failedProducts: failedProducts,
            pollInterval: null,
            startTime: null,
            eta: null,

            get processedCount() {
                return this.syncedProducts + this.failedProducts;
            },

            get overallPercent() {
                if (this.totalProducts === 0) return 0;
                return Math.round((this.processedCount / this.totalProducts) * 100);
            },

            get successPercent() {
                if (this.totalProducts === 0) return 0;
                return Math.round((this.syncedProducts / this.totalProducts) * 100);
            },

            get failedPercent() {
                if (this.totalProducts === 0) return 0;
                return Math.round((this.failedProducts / this.totalProducts) * 100);
            },

            get isActive() {
                return this.status === 'running' || this.status === 'pending';
            },

            get etaFormatted() {
                if (this.eta === null || this.eta <= 0) return '-';

                const minutes = Math.floor(this.eta / 60);
                const seconds = Math.round(this.eta % 60);

                if (minutes > 0) {
                    return minutes + 'm ' + seconds + 's';
                }

                return seconds + 's';
            },

            init() {
                this.startTime = Date.now();

                if (this.isActive) {
                    this.startPolling();
                }
            },

            startPolling() {
                this.pollInterval = setInterval(() => {
                    this.fetchStatus();
                }, 3000);
            },

            stopPolling() {
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                }
            },

            async fetchStatus() {
                try {
                    const response = await fetch(
                        '{{ route("admin.channel_connector.dashboard.status", ":id") }}'.replace(':id', this.jobId),
                        {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                        }
                    );

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    this.status = data.status;
                    this.totalProducts = data.total_products || 0;
                    this.syncedProducts = data.synced_products || 0;
                    this.failedProducts = data.failed_products || 0;

                    this.calculateEta();

                    if (!this.isActive) {
                        this.stopPolling();

                        if (this.status === 'completed' || this.status === 'failed') {
                            window.location.reload();
                        }
                    }
                } catch (error) {
                    // Silently handle polling errors to avoid disrupting the UI
                }
            },

            calculateEta() {
                if (!this.isActive || this.processedCount === 0 || this.totalProducts === 0) {
                    this.eta = null;
                    return;
                }

                const elapsedSeconds = (Date.now() - this.startTime) / 1000;
                const rate = this.processedCount / elapsedSeconds; // products per second
                const remaining = this.totalProducts - this.processedCount;

                if (rate > 0) {
                    this.eta = remaining / rate;
                } else {
                    this.eta = null;
                }
            },
        };
    }
</script>
