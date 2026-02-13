<?php

namespace Webkul\Completeness\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Tenant\Jobs\TenantAwareJob;

class BulkProductCompletenessJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    protected const CHUNK_SIZE = 100;

    protected const BATCH_SIZE = 1000;

    protected $productRepository;

    public $tries = 3;

    public $uniqueFor = 300;

    public function __construct(
        protected array $product = [],
        protected ?int $familyId = null,
    ) {
        $this->queue = 'system';

        $this->captureTenantContext();
    }

    public function uniqueId(): string
    {
        $prefix = $this->tenantId ? "{$this->tenantId}-" : '';

        if (is_null($this->familyId)) {
            return uniqid("{$prefix}completeness-job-", true);
        }

        return "{$prefix}completeness-job-{$this->familyId}";
    }

    public function handle(): void
    {
        $this->productRepository = app(ProductRepository::class);

        try {
            if ($this->familyId) {
                $this->processFamilyProducts();
            } else {
                $this->dispatchInChunks($this->product);
            }
        } catch (Throwable $e) {
            logger()->error($e);
        }
    }

    protected function processFamilyProducts(): void
    {
        $page = 1;

        do {
            $products = $this->productRepository
                ->select('id')
                ->where('attribute_family_id', $this->familyId)
                ->forPage($page, self::BATCH_SIZE)
                ->pluck('id');

            if ($products->isEmpty()) {
                break;
            }

            $this->dispatchInChunks($products->toArray());

            $page++;
        } while ($products->count() === self::BATCH_SIZE);
    }

    protected function dispatchInChunks(array $productIds): void
    {
        $tenantPackageActive = class_exists(\Webkul\Tenant\Providers\TenantServiceProvider::class);

        if ($tenantPackageActive && is_null($this->tenantId)) {
            \Illuminate\Support\Facades\Log::warning('BulkProductCompletenessJob: No tenant context, skipping child dispatches');

            return;
        }

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            ProductCompletenessJob::dispatch($chunk);
        }
    }
}
