<?php

namespace Webkul\Product\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Product\Models\ProductProxy;

#[Description('Rebuild derived data (completeness scores, search index) for variant subtrees. A safety net when a queued propagation job was dropped; the source tree is always authoritative.')]
#[Signature('unopim:variants:resync
                            {--product= : Limit to a single configurable product id}
                            {--all : Resync every configurable product}')]
class ResyncVariantsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('product') && ! $this->option('all')) {
            $this->error('Specify --product=<id> or --all.');

            return self::FAILURE;
        }

        $roots = ProductProxy::modelClass()::query()
            ->where('type', 'configurable')
            ->with('variants.variants');

        if ($id = $this->option('product')) {
            $roots->where('id', $id);
        }

        $ids = [];

        $roots->chunkById(200, function ($products) use (&$ids): void {
            foreach ($products as $product) {
                $ids[] = $product->id;

                foreach ($product->variants as $child) {
                    $ids[] = $child->id;

                    foreach ($child->variants as $grandChild) {
                        $ids[] = $grandChild->id;
                    }
                }
            }
        });

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            $this->info('No variant trees to resync.');

            return self::SUCCESS;
        }

        // Recompute completeness for the whole subtree. Re-saving would also fire
        // the Elasticsearch observer; completeness is the derived data we own here.
        dispatch(new ProductCompletenessJob($ids));

        $this->info(sprintf('Queued resync for %d product(s) across the matched variant tree(s).', count($ids)));

        return self::SUCCESS;
    }
}
