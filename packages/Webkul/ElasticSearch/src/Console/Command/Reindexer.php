<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\Core\Facades\ElasticSearch;

class Reindexer extends Command
{
    protected $signature = 'elastic:clear';

    protected $description = 'Clear all indexes for this project from Elasticsearch.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (config('elasticsearch.connection')) {
            if ($this->confirm('This action will clear all indexes for this project. Do you want to continue? (y/n) or', false)) {
                $indexPrefix = config('elasticsearch.prefix');

                $start = microtime(true);

                $productIndex = strtolower($indexPrefix.'_products');

                $categoryIndex = strtolower($indexPrefix.'_categories');

                try {
                    Elasticsearch::indices()->delete(['index' => $productIndex]);
                    $this->info('Elasticsearch '.$productIndex.' index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$productIndex);
                    } else {
                        throw $e;
                    }
                }

                try {
                    Elasticsearch::indices()->delete(['index' => $categoryIndex]);
                    $this->info('Elasticsearch '.$categoryIndex.' index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$categoryIndex);
                    } else {
                        throw $e;
                    }
                }

                $end = microtime(true);

                $this->info('The operation took '.round($end - $start, 4).' seconds to complete.');
            } else {
                $this->info('Action canceled.');
            }
        } else {
            $this->warn('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
