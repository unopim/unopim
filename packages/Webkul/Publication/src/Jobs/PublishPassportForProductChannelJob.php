<?php

namespace Webkul\Publication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Services\Publisher;

/**
 * One dispatch per (product, channel, type) admin action, looping locales
 * inside `handle()` — not one dispatch per locale, which would re-fetch the
 * same product/family once per locale for no reason.
 */
class PublishPassportForProductChannelJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * Cap the uniqueness lock so a job that never runs (e.g. dispatched to a
     * queue no worker consumes) cannot block re-publishing the same product
     * indefinitely — the lock self-expires after an hour.
     */
    public int $uniqueFor = 3600;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param  list<int>  $localeIds
     */
    public function __construct(
        private readonly int $productId,
        private readonly int $channelId,
        private readonly string $type,
        private readonly array $localeIds,
        private readonly ?int $publishedById = null,
    ) {
        $this->onQueue(config('publication.queue'));
    }

    public function uniqueId(): string
    {
        return "{$this->productId}:{$this->channelId}:{$this->type}";
    }

    public function handle(Publisher $publisher): void
    {
        $product = ProductProxy::modelClass()::find($this->productId);

        if (! $product) {
            return;
        }

        $channel = ChannelProxy::modelClass()::find($this->channelId);

        if (! $channel) {
            return;
        }

        $locales = LocaleProxy::modelClass()::whereIn('id', $this->localeIds)->get()->keyBy('id');

        // Publisher::publish() already wraps each locale in its own short,
        // lockForUpdate()-guarded transaction.
        foreach ($this->localeIds as $localeId) {
            $locale = $locales->get($localeId);

            if ($locale) {
                $publisher->publish($product, $channel, $locale, $this->type, $this->publishedById);
            }
        }
    }
}
