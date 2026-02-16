<?php

namespace Webkul\Tenant\Tests\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Webkul\Tenant\Jobs\TenantSandbox;
use Webkul\Tenant\Models\Tenant;

class TestTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * The data to process.
     *
     * @var mixed
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param mixed $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->captureTenantContext();
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        // Simulate processing
        return [
            'status' => 'success',
            'tenant_id' => $this->tenantId,
            'data' => $this->data,
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new TenantSandbox];
    }

    /**
     * Get the queue that should be used for job dispatching.
     *
     * @return string|null
     */
    public function queue(): ?string
    {
        return $this->resolveTenantQueue('jobs');
    }

    /**
     * The job failed to process.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // Log failure with tenant context
        info("TestTenantJob failed for tenant {$this->tenantId}", [
            'exception' => $exception->getMessage(),
            'tenant_id' => $this->tenantId,
        ]);
    }
}