<?php

namespace Webkul\Admin\Helpers;

use Webkul\User\Repositories\AdminPromoDismissalRepository;

class PromoBanner
{
    /**
     * Create a new helper instance.
     */
    public function __construct(
        protected AdminPromoDismissalRepository $dismissalRepository,
        protected VersionCheck $versionCheck,
    ) {}

    /**
     * Resolve the promo banners visible to the currently authenticated admin.
     *
     * @return array<int, array<string, mixed>>
     */
    public function visibleBanners(): array
    {
        $adminId = auth()->guard('admin')->id();

        if (! $adminId) {
            return [];
        }

        $dismissed = $this->dismissalRepository->dismissedFor($adminId)
            ->map(fn ($row) => $row->banner.'|'.$row->version)
            ->all();

        $banners = [];

        foreach (config('help.banners', []) as $banner) {
            if (! empty($banner['requires_outdated']) && ! $this->versionCheck->isOutdated()) {
                continue;
            }

            if (! empty($banner['hide_on_cloud']) && config('help.is_cloud')) {
                continue;
            }

            $version = $banner['key'] === 'upgrade'
                ? (string) $this->versionCheck->latestVersion()
                : '';

            if (in_array($banner['key'].'|'.$version, $dismissed, true)) {
                continue;
            }

            $banners[] = [
                'key'     => $banner['key'],
                'icon'    => $banner['icon'],
                'tag'     => trans($banner['tag']),
                'message' => trans($banner['message'], ['version' => $this->versionCheck->currentVersion()]),
                'cta'     => trans($banner['cta']),
                'url'     => $banner['url'],
                'version' => $version,
            ];
        }

        return $banners;
    }
}
