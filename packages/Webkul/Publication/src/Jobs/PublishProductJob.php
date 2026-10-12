<?php

namespace Webkul\Publication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Services\Publisher;

class PublishProductJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * A transient failure (deadlock while waiting on the publication row
     * lock, DB blip) should retry rather than die silently with no attempts.
     */
    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $productId,
        private readonly int $channelId,
        private readonly int $localeId,
        private readonly string $type,
        private readonly ?int $publishedById = null,
    ) {
        $this->onQueue(config('publication.queue'));
    }

    public function handle(Publisher $publisher): void
    {
        $product = ProductProxy::modelClass()::find($this->productId);

        if (! $product) {
            return;
        }

        $publisher->publish(
            $product,
            ChannelProxy::modelClass()::findOrFail($this->channelId),
            LocaleProxy::modelClass()::findOrFail($this->localeId),
            $this->type,
            $this->publishedById,
        );
    }
}
