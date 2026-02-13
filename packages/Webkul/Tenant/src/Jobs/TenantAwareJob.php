<?php

namespace Webkul\Tenant\Jobs;

/**
 * TenantAwareJob trait — Stories 4.1, 4.4, 4.5.
 *
 * Add this trait to any ShouldQueue job to automatically:
 * - Serialize tenant_id into the job payload on dispatch (FR28)
 * - Restore tenant context before job execution (FR29)
 * - Reset context after execution via TenantSandbox (FR31)
 * - Route to per-tenant queue for fairness (FR33)
 * - Retain tenant_id in failed_jobs for debugging (FR30)
 */
trait TenantAwareJob
{
    /**
     * The tenant ID captured at dispatch time.
     * Serialized automatically into the job payload.
     */
    public ?int $tenantId = null;

    /**
     * Capture the current tenant context.
     * Call this as the last line in your constructor.
     */
    public function captureTenantContext(): void
    {
        if (is_null($this->tenantId)) {
            $this->tenantId = core()->getCurrentTenantId();
        }
    }

    /**
     * Get the middleware the job should pass through.
     * TenantSandbox restores tenant context before handle() and cleans up after.
     */
    public function middleware(): array
    {
        return [new TenantSandbox];
    }

    /**
     * Get the queue name for this job — routes to per-tenant queue for fairness (FR33).
     * Workers can listen on `tenant-*` pattern or specific queues.
     */
    public function resolveTenantQueue(?string $baseQueue = null): string
    {
        $base = $baseQueue ?? 'default';

        if (is_null($this->tenantId)) {
            return $base;
        }

        return "tenant-{$this->tenantId}-{$base}";
    }
}
