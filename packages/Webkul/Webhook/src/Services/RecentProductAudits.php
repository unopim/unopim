<?php

namespace Webkul\Webhook\Services;

use OwenIt\Auditing\Contracts\Audit;

/**
 * The audits written for each product during the current request.
 *
 * Webhook payloads report what a write changed, and the audit row holding that diff
 * can only be identified by the write that produced it. Matching on clock proximity
 * instead drops the webhook whenever the request outruns the window.
 */
class RecentProductAudits
{
    /**
     * @var array<int, Audit>
     */
    protected array $audits = [];

    public function remember(int|string $productId, Audit $audit): void
    {
        $this->audits[(int) $productId] = $audit;
    }

    public function for(int|string $productId): ?Audit
    {
        return $this->audits[(int) $productId] ?? null;
    }
}
