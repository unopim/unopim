<?php

namespace Webkul\ProductPassport\Listeners;

use Webkul\Attribute\Models\AttributeFamilyGroupMappingProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\Product;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;

/**
 * Wires the `catalog.product_passport.settings.auto_publish` setting: when a
 * product is saved, queue a passport publish for every channel where both
 * `enabled` and `auto_publish` are on. The job's `CompletenessGate` skips any
 * locale below the channel threshold, so an incomplete save publishes nothing;
 * the job is `ShouldBeUnique`, so rapid re-saves de-dupe rather than pile up.
 *
 * Guarded to `dpp`-family products only — a save of an unrelated product must
 * never spawn a passport job.
 */
class AutoPublishPassport
{
    public function handle(Product $product): void
    {
        if (! $this->familyHasDppGroup($product)) {
            return;
        }

        $adminId = auth()->guard('admin')->id();

        ChannelProxy::modelClass()::query()
            ->with('locales:id')
            ->get()
            ->each(function ($channel) use ($product, $adminId): void {
                $enabled = (bool) (core()->getConfigData('catalog.product_passport.settings.enabled', $channel->code) ?? false);
                $autoPublish = (bool) (core()->getConfigData('catalog.product_passport.settings.auto_publish', $channel->code) ?? false);

                if (! $enabled || ! $autoPublish) {
                    return;
                }

                $localeIds = $channel->locales->pluck('id')->map(fn ($id): int => (int) $id)->all();

                if ($localeIds === []) {
                    return;
                }

                PublishPassportForProductChannelJob::dispatch($product->id, $channel->id, 'dpp', $localeIds, $adminId);
            });
    }

    private function familyHasDppGroup(Product $product): bool
    {
        if ($product->attribute_family_id === null) {
            return false;
        }

        return AttributeFamilyGroupMappingProxy::modelClass()::query()
            ->join('attribute_groups', 'attribute_family_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->where('attribute_groups.code', 'dpp')
            ->where('attribute_family_group_mappings.attribute_family_id', $product->attribute_family_id)
            ->exists();
    }
}
