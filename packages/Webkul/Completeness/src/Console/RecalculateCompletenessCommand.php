<?php

namespace Webkul\Completeness\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Product\Repositories\ProductRepository;

class RecalculateCompletenessCommand extends Command
{
    protected const BATCH_SIZE = 1000;

    protected $signature = 'unopim:completeness:recalculate
                            {--family= : Recalculate for a specific attribute family ID}
                            {--product= : Recalculate for a specific product ID}
                            {--products=* : Recalculate for a list of product IDs. Multiple --products can be provided which will work as a list of IDs like --products=1 --products=2}
                            {--all : Recalculate for all products}
                            {--tenant= : Tenant ID to scope recalculation}';

    protected $description = 'Recalculate product completeness based on various criteria (product, products list, family, or all).';

    public function __construct(protected ProductRepository $productRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('tenant') && class_exists(\Webkul\Tenant\Providers\TenantServiceProvider::class)) {
            $this->error('Multi-tenant mode detected. You must specify --tenant or run for each tenant individually.');

            return Command::FAILURE;
        }

        if ($tenantOption = $this->option('tenant')) {
            $tenant = DB::table('tenants')->where('id', $tenantOption)->first();
            if (! $tenant || $tenant->status !== 'active') {
                $this->error('Tenant not found or not active.');

                return Command::FAILURE;
            }
            core()->setCurrentTenantId((int) $tenantOption);
            $this->info("Running in tenant context: {$tenant->name} (ID: {$tenant->id})");
        }
        if ($productId = $this->option('product')) {
            return $this->handleSingleProduct($productId);
        }

        if ($familyId = $this->option('family')) {
            return $this->handleFamily($familyId);
        }

        $productIds = $this->option('products');

        if (! empty($productIds)) {
            return $this->handleMultipleProducts($productIds);
        }

        if ($this->option('all')) {
            return $this->handleAllProducts();
        }

        return $this->handleFallback();
    }

    protected function handleSingleProduct($productId): int
    {
        $this->info("Dispatching completeness job for product ID: {$productId}");

        ProductCompletenessJob::dispatch([$productId]);

        return Command::SUCCESS;
    }

    protected function handleFamily($familyId): int
    {
        $this->info("Dispatching completeness job for family ID: {$familyId}");

        BulkProductCompletenessJob::dispatch([], $familyId);

        return Command::SUCCESS;
    }

    protected function handleMultipleProducts(array $productIds): int
    {
        $this->info('Dispatching completeness job for product IDs: '.implode(', ', $productIds));

        ProductCompletenessJob::dispatch($productIds);

        return Command::SUCCESS;
    }

    protected function handleAllProducts(): int
    {
        $this->info('Dispatching completeness job for all products...');

        $this->productRepository
            ->select('id')
            ->orderBy('id')
            ->chunkById(self::BATCH_SIZE, function ($products) {
                BulkProductCompletenessJob::dispatch($products->pluck('id')->toArray());
            });

        return Command::SUCCESS;
    }

    protected function handleFallback(): int
    {
        $this->error('No valid options provided. Please specify one of: --all, --family, --product, or --products.');

        if ($this->confirm('Do you want to recalculate completeness for all products?', false)) {
            return $this->handleAllProducts();
        }

        return Command::FAILURE;
    }
}
