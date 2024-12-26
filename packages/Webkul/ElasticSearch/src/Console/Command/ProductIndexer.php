<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

class ProductIndexer extends Command
{
    protected $signature = 'product:index';

    protected $description = 'Index all into Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {

            $start = microtime(true);

            // Indexing Products
            $products = Product::all();

            $productIndex = strtolower('products');

            $dbProductIds = $products->pluck('id')->toArray();

            try {
                $elasticProductIds = collect(Elasticsearch::search([
                    'index' => $productIndex,
                    'body'  => [
                        '_source' => false, // Fetch only IDs
                        'query'   => [
                            'match_all' => new \stdClass,
                        ],
                    ],
                ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

                $productsToDelete = array_diff($elasticProductIds, $dbProductIds);

                foreach ($productsToDelete as $productId) {
                    Elasticsearch::delete([
                        'index' => $productIndex,
                        'id'    => $productId,
                    ]);
                }
            } catch (\Exception $e) {}

            foreach ($products as $product) {
                Elasticsearch::index([
                    'index' => $productIndex,
                    'id'    => $product->id,
                    'body'  => $product->toArray(),
                ]);
            }

            $this->info('Products indexed successfully!');

            $end = microtime(true);

            echo 'The code took '.($end - $start)." seconds to complete.\n";
        } else {
            $this->info('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
