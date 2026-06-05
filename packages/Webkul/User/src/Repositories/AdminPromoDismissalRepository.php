<?php

namespace Webkul\User\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\User\Contracts\AdminPromoDismissal;

class AdminPromoDismissalRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AdminPromoDismissal::class;
    }

    /**
     * Record a dismissal for an admin (idempotent).
     */
    public function dismiss(int $adminId, string $banner, string $version = ''): void
    {
        $existing = $this->findWhere([
            'admin_id' => $adminId,
            'banner'   => $banner,
            'version'  => $version,
        ]);

        if ($existing->isNotEmpty()) {
            return;
        }

        $this->create([
            'admin_id' => $adminId,
            'banner'   => $banner,
            'version'  => $version,
        ]);
    }

    /**
     * Dismissal rows (banner + version) for the given admin.
     */
    public function dismissedFor(int $adminId): Collection
    {
        return $this->findWhere(['admin_id' => $adminId], ['banner', 'version']);
    }
}
