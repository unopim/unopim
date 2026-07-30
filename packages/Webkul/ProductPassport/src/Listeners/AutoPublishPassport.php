<?php

namespace Webkul\ProductPassport\Listeners;

use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Services\PassportFeature;
use Webkul\ProductPassport\Services\PassportTemplateResolver;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;

/**
 * Wires the `catalog.product_passport.settings.auto_publish` setting: when a
 * product is saved, queue a passport publish for every channel where both
 * `enabled` and `auto_publish` are on. The publish itself is gated by the
 * template readiness check, so an incomplete save publishes nothing; the job
 * is `ShouldBeUnique`, so rapid re-saves de-dupe rather than pile up.
 *
 * Guarded to products whose family carries a passport template — a save of an
 * unrelated product must never spawn a passport job.
 */
class AutoPublishPassport
{
    public function __construct(
        private readonly PassportTemplateResolver $templates,
        private readonly PassportFeature $feature,
    ) {}

    public function handle(Product $product): void
    {
        if ($this->templates->forProduct($product) === null) {
            return;
        }

        $adminId = auth()->guard('admin')->id();

        ChannelProxy::modelClass()::query()
            ->with('locales:id')
            ->get()
            ->each(function ($channel) use ($product, $adminId): void {
                if (! $this->feature->autoPublishEnabledFor($channel)) {
                    return;
                }

                $localeIds = $channel->locales->pluck('id')->map(fn ($id): int => (int) $id)->all();

                if ($localeIds === []) {
                    return;
                }

                PublishPassportForProductChannelJob::dispatch($product->id, $channel->id, 'dpp', $localeIds, $adminId);
            });
    }
}
