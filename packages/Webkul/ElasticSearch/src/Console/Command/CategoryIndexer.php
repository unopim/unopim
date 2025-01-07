<?php

namespace Webkul\ElasticSearch\Console\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkul\Category\Models\Category;
use Webkul\Core\Facades\ElasticSearch;

class CategoryIndexer extends Command
{
    protected $signature = 'category:index';

    protected $description = 'Index all categories into Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            $indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');

            $start = microtime(true);

            $categories = Category::all();

            $categoryIndex = strtolower($indexPrefix.'_categories');

            if ($categories->isNotEmpty()) {
                $elasticCategory = [];

                try {
                    $elasticCategory = collect(Elasticsearch::search([
                        'index' => $categoryIndex,
                        'body'  => [
                            '_source' => ['updated_at'],
                            'query'   => [
                                'match_all' => new \stdClass,
                            ],
                            'size' => 1000000000,
                        ],
                    ])['hits']['hits'])->mapWithKeys(function ($hit) {
                        return [(int) $hit['_id'] => $hit['_source']['updated_at']];
                    })->toArray();
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->info('No data found. Initiating fresh indexing');
                    } else {
                        throw $e;
                    }
                }

                $this->info('Indexing categories into Elasticsearch...');
                $dbCategoryIds = $categories->pluck('id')->toArray();

                $progressBar = new ProgressBar($this->output, count($categories));
                $progressBar->start();

                foreach ($categories as $category) {
                    if (
                        (
                            isset($elasticCategory[$category->id])
                            && $elasticCategory[$category->id] != Carbon::parse($category->updated_at)->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z')
                        )
                        || ! isset($elasticCategory[$category->id])
                    ) {

                        Elasticsearch::index([
                            'index' => $categoryIndex,
                            'id'    => $category->id,
                            'body'  => $category->toArray(),
                        ]);
                    }
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
                $this->info('Category indexing completed.');

                $this->info('Checking for stale categories to delete...');

                $elasticCategoryIds = collect(Elasticsearch::search([
                    'index' => $categoryIndex,
                    'body'  => [
                        '_source' => false,
                        'query'   => [
                            'match_all' => new \stdClass,
                        ],
                        'size' => 1000000000,
                    ],
                ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

                $categoriesToDelete = array_diff($elasticCategoryIds, $dbCategoryIds);

                if (! empty($categoriesToDelete)) {
                    $this->info('Deleting stale categories from Elasticsearch...');
                    $deleteProgressBar = new ProgressBar($this->output, count($categoriesToDelete));
                    $deleteProgressBar->start();

                    foreach ($categoriesToDelete as $categoryId) {
                        Elasticsearch::delete([
                            'index' => $categoryIndex,
                            'id'    => $categoryId,
                        ]);
                        $deleteProgressBar->advance();
                    }

                    $deleteProgressBar->finish();
                    $this->newLine();
                    $this->info('Stale categories deleted successfully.');
                } else {
                    $this->info('No stale categories to delete.');
                }
            } else {
                try {
                    Elasticsearch::indices()->delete(['index' => $categoryIndex]);
                    $this->info('Elasticsearch index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$categoryIndex);
                    } else {
                        throw $e;
                    }
                }
            }

            $end = microtime(true);

            $this->info('The operation took '.round($end - $start, 4).' seconds to complete.');
        } else {
            $this->warn('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
