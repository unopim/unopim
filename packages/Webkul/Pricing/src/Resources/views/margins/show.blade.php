<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.margins.show.title')
    </x-slot>

    <v-margin-event-detail :event="{{ json_encode($event) }}"></v-margin-event-detail>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-margin-event-detail-template">
            <div>
                <div class="flex justify-between items-center mb-4">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        @lang('pricing::app.margins.show.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a
                            href="{{ route('admin.pricing.margins.index') }}"
                            class="transparent-button"
                        >
                            @lang('pricing::app.margins.show.back-btn')
                        </a>

                        @if (bouncer()->hasPermission('pricing.margins.approve') && $event->event_type === 'blocked')
                            <button
                                @click="approve"
                                class="primary-button"
                            >
                                @lang('pricing::app.margins.show.approve-btn')
                            </button>

                            <button
                                @click="reject"
                                class="secondary-button"
                            >
                                @lang('pricing::app.margins.show.reject-btn')
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Event Status Badge -->
                <div class="mb-4">
                    @php
                        $badgeClass = match($event->event_type) {
                            'blocked' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'rejected' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                        };
                    @endphp
                    <span class="px-4 py-2 {{ $badgeClass }} rounded text-sm font-semibold uppercase">
                        @lang('pricing::app.margins.show.status.' . $event->event_type)
                    </span>
                </div>

                <!-- Event Details Grid -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Product & Channel Info -->
                    <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                        <p class="mb-3 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('pricing::app.margins.show.product-channel-info')
                        </p>

                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-300">@lang('pricing::app.margins.show.product'):</span>
                                <span class="text-sm text-gray-800 dark:text-white font-medium">{{ $event->product->sku ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-300">@lang('pricing::app.margins.show.channel'):</span>
                                <span class="text-sm text-gray-800 dark:text-white font-medium">{{ $event->channel->code ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Breakdown -->
                    <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                        <p class="mb-3 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('pricing::app.margins.show.pricing-breakdown')
                        </p>

                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-300">@lang('pricing::app.margins.show.proposed-price'):</span>
                                <span class="text-sm text-gray-800 dark:text-white font-medium">{{ number_format($event->proposed_price, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-300">@lang('pricing::app.margins.show.break-even-price'):</span>
                                <span class="text-sm text-gray-800 dark:text-white font-medium">{{ number_format($event->break_even_price, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-300">@lang('pricing::app.margins.show.minimum-margin-price'):</span>
                                <span class="text-sm text-gray-800 dark:text-white font-medium">{{ number_format($event->minimum_margin_price, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-300">@lang('pricing::app.margins.show.target-margin-price'):</span>
                                <span class="text-sm text-gray-800 dark:text-white font-medium">{{ number_format($event->target_margin_price, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Margin Percentages -->
                <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded mb-4">
                    <p class="mb-3 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('pricing::app.margins.show.margin-analysis')
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 bg-gray-50 dark:bg-cherry-800 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">@lang('pricing::app.margins.show.actual-margin')</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                                {{ number_format($event->margin_percentage, 2) }}%
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-cherry-800 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">@lang('pricing::app.margins.show.minimum-required-margin')</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                                {{ number_format($event->minimum_margin_percentage, 2) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Reason & Approval Info -->
                <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded mb-4">
                    <p class="mb-3 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('pricing::app.margins.show.additional-info')
                    </p>

                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">@lang('pricing::app.margins.show.reason'):</p>
                            <p class="text-sm text-gray-800 dark:text-white">{{ $event->reason ?? 'N/A' }}</p>
                        </div>

                        @if ($event->approved_by)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">@lang('pricing::app.margins.show.approved-by'):</p>
                                <p class="text-sm text-gray-800 dark:text-white">{{ $event->approver->name ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">@lang('pricing::app.margins.show.approved-at'):</p>
                                <p class="text-sm text-gray-800 dark:text-white">{{ $event->approved_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</p>
                            </div>
                        @endif

                        @if ($event->expires_at)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">@lang('pricing::app.margins.show.expires-at'):</p>
                                <p class="text-sm text-gray-800 dark:text-white">{{ $event->expires_at->format('Y-m-d H:i:s') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-margin-event-detail', {
                template: '#v-margin-event-detail-template',
                props: ['event'],

                methods: {
                    approve() {
                        if (!confirm('@lang('pricing::app.margins.show.confirm-approve')')) return;

                        this.$axios.post('{{ route('admin.pricing.margins.approve', $event->id) }}')
                            .then(response => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                window.location.reload();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || '@lang('pricing::app.margins.show.approve-error')'
                                });
                            });
                    },

                    reject() {
                        if (!confirm('@lang('pricing::app.margins.show.confirm-reject')')) return;

                        this.$axios.post('{{ route('admin.pricing.margins.reject', $event->id) }}')
                            .then(response => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                window.location.reload();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || '@lang('pricing::app.margins.show.reject-error')'
                                });
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
