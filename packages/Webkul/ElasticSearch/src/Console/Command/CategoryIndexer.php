<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\Category\Models\Category;
use Webkul\Core\Facades\ElasticSearch;
use Symfony\Component\Console\Helper\ProgressBar;

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

            if ($categories->isNotEmpty()) {
                $this->info('Indexing categories into Elasticsearch...');
                $categoryIndex = strtolower($indexPrefix . '_categories');
                $dbCategoryIds = $categories->pluck('id')->toArray();

                $progressBar = new ProgressBar($this->output, count($categories));
                $progressBar->start();

                foreach ($categories as $category) {
                    Elasticsearch::index([
                        'index' => $categoryIndex,
                        'id'    => $category->id,
                        'body'  => $category->toArray(),
                    ]);
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
                    ],
                ])['hits']['hits'])->pluck('_id')->map(fn($id) => (int) $id)->toArray();

                $categoriesToDelete = array_diff($elasticCategoryIds, $dbCategoryIds);

                if (!empty($categoriesToDelete)) {
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
                $this->info('No categories found.');
            }

            $end = microtime(true);

            $this->info('The operation took ' . round($end - $start, 4) . ' seconds to complete.');
        } else {
            $this->warn('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
