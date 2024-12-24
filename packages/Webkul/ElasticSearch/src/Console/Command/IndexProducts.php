<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\ElasticSearch\Services\ElasticsearchService;
use Webkul\Product\Models\Product;

class IndexProducts extends Command
{
    protected $signature = 'products:index';

    protected $description = 'Index all products into Elasticsearch';

    private $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        parent::__construct();
        $this->elasticsearchService = $elasticsearchService;
    }

    public function handle()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $this->elasticsearchService->index(strtolower('products_'.core()->getRequestedChannelCode().'_'.core()->getRequestedLocaleCode().'_index'), $product->id, $product->toArray());
        }

        $this->info('Products indexed successfully!');
    }
}
