<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\Category\Models\Category;
use Webkul\Core\Facades\ElasticSearch;

class CategoryIndexer extends Command
{
    protected $signature = 'category:index';

    protected $description = 'Index all into Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {

            $start = microtime(true);

            // Indexing Categories
            $categories = Category::all();

            $dbCategoryIds = $categories->pluck('id')->toArray();

            try {
                $elasticCategoryIds = collect(Elasticsearch::search([
                    'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_categories'),
                    'body'  => [
                        '_source' => false,
                        'query'   => [
                            'match_all' => new \stdClass,
                        ],
                    ],
                ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

                $categoriesToDelete = array_diff($elasticCategoryIds, $dbCategoryIds);

                foreach ($categoriesToDelete as $categoryId) {
                    Elasticsearch::delete([
                        'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_categories'),
                        'id'    => $categoryId,
                    ]);
                }
            } catch (\Exception $e) {
            }

            foreach ($categories as $category) {
                Elasticsearch::index([
                    'index' => strtolower(env('ELASTICSEARCH_INDEX_PREFIX').'_categories'),
                    'id'    => $category->id,
                    'body'  => $category->toArray(),
                ]);
            }

            $this->info('Categories indexed successfully!');

            $end = microtime(true);

            echo 'The code took '.($end - $start)." seconds to complete.\n";
        } else {
            $this->info('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
