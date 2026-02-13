<?php

namespace Webkul\Tenant\Jobs;

/**
 * TenantSandbox — Job middleware that provides isolated tenant context (FR31).
 *
 * - Restores tenant context from serialized job payload before handle()
 * - Clears tenant context after handle() completes (success or failure)
 * - Prevents context contamination between sequential jobs on the same worker
 */
class TenantSandbox
{
    /**
     * Process the queued job within an isolated tenant sandbox.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        // Restore tenant context from serialized payload
        if (isset($job->tenantId) && ! is_null($job->tenantId)) {
            core()->setCurrentTenantId($job->tenantId);
        }

        try {
            return $next($job);
        } finally {
            // Always reset tenant context — prevents contamination between jobs
            core()->setCurrentTenantId(null);
        }
    }
}
