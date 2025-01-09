<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
                    $this->info($productIndex.' index deleted successfully.');

                    Log::channel('elasticsearch')->info($productIndex.' index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$productIndex);

                        Log::channel('elasticsearch')->warning($productIndex.' index not found: ', [
                            'warning' => $e->getMessage(),
                        ]);
                    } else {
                        Log::channel('elasticsearch')->error('Exception while clearing '.$productIndex.' index: ', [
                            'error' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }

                try {
                    Elasticsearch::indices()->delete(['index' => $categoryIndex]);
                    $this->info($categoryIndex.' index deleted successfully.');

                    Log::channel('elasticsearch')->info($categoryIndex.' index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$categoryIndex);

                        Log::channel('elasticsearch')->warning($categoryIndex.'index not found: ', [
                            'warning' => $e->getMessage(),
                        ]);
                    } else {
                        Log::channel('elasticsearch')->error('Exception while clearing '.$categoryIndex.' index: ', [
                            'error' => $e->getMessage(),
                        ]);

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

            Log::channel('elasticsearch')->warning('ELASTICSEARCH IS NOT ENABLE.');
        }
    }
}
