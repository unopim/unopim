<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Traits\ResolveTenantIndex;

class Reindexer extends Command
{
    use ResolveTenantIndex;

    protected $signature = 'unopim:elastic:clear {--tenant= : Tenant ID to scope clearing}';

    protected $description = 'Clear all indexes for this project from Elasticsearch.';

    private $indexPrefix;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (! $this->option('tenant') && class_exists(\Webkul\Tenant\Providers\TenantServiceProvider::class)) {
            $this->error('Multi-tenant mode detected. You must specify --tenant or run for each tenant individually.');

            return 1;
        }

        if (config('elasticsearch.enabled')) {
            $scope = $this->option('tenant') ? 'tenant '.$this->option('tenant') : 'this project';

            if ($this->confirm("This action will clear all indexes for {$scope}. Do you want to continue? (y/n) or", false)) {
                $this->indexPrefix = config('elasticsearch.prefix');

                if ($tenantOption = $this->option('tenant')) {
                    $tenant = DB::table('tenants')->where('id', $tenantOption)->first();
                    if (! $tenant || $tenant->status !== 'active') {
                        $this->error('Tenant not found or not active.');

                        return 1;
                    }
                    core()->setCurrentTenantId((int) $tenantOption);
                }

                $this->initTenantIndex();

                $start = microtime(true);

                $productIndex = $this->tenantAwareIndexName('products');

                $categoryIndex = $this->tenantAwareIndexName('categories');

                try {
                    ElasticSearch::indices()->delete(['index' => $productIndex]);
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
                    ElasticSearch::indices()->delete(['index' => $categoryIndex]);
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

            Log::channel('elasticsearch')->warning('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
