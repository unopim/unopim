@inject('scoreThresholds', 'Webkul\Completeness\Services\ScoreThresholds')

@pushOnce('scripts')
    <script type="module">
        app.config.globalProperties.$completeness = {
            thresholds: @json($scoreThresholds->toArray()),

            level(score) {
                if (score >= this.thresholds.greenFrom) {
                    return 'success';
                }

                if (score < this.thresholds.redBelow) {
                    return 'danger';
                }

                return 'warning';
            },
        };
    </script>
@endPushOnce
