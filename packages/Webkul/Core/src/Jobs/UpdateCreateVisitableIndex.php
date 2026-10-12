<?php

namespace Webkul\Core\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class UpdateCreateVisitableIndex implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array  $log
     */
    public function __construct(protected $log) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $slugOrURLKey = urldecode(trim($this->log['path_info'], '/'));

        /**
         * Support url for chinese, japanese, arabic and english with numbers.
         */
        if (! preg_match('/^([\x{0621}-\x{064A}\x{4e00}-\x{9fa5}\x{3402}-\x{FA6D}\x{3041}-\x{30A0}\x{30A0}-\x{31FF}_a-z0-9-]+\/?)+$/u', $slugOrURLKey)) {
            dispatch(new UpdateCreateVisitIndex(null, $this->log));

            return;
        }

        $category = resolve(CategoryRepository::class)->findBySlug($slugOrURLKey);

        if ($category) {
            dispatch(new UpdateCreateVisitIndex($category, $this->log));

            return;
        }

        $product = resolve(ProductRepository::class)->findBySlug($slugOrURLKey);

        if (
            ! $product
            || ! $product->visible_individually
            || ! $product->url_key
            || ! $product->status
        ) {
            return;
        }

        dispatch(new UpdateCreateVisitIndex($product, $this->log));
    }
}
