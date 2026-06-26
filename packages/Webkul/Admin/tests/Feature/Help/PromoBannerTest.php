<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\PromoBanner;
use Webkul\Admin\Helpers\VersionCheck;
use Webkul\User\Repositories\AdminPromoDismissalRepository;

function seedLatestVersion(string $version): void
{
    Cache::put('unopim_latest_version', $version, now()->addHour());
}

it('reports outdated when cached latest is greater than current', function () {
    seedLatestVersion('9.9.9');

    expect(app(VersionCheck::class)->isOutdated())->toBeTrue();
});

it('reports up to date when cached latest equals current', function () {
    seedLatestVersion(core()->version());

    expect(app(VersionCheck::class)->isOutdated())->toBeFalse();
});

it('includes upgrade banner when outdated', function () {
    $this->loginAsAdmin();
    seedLatestVersion('9.9.9');

    $keys = collect(app(PromoBanner::class)->visibleBanners())->pluck('key');

    expect($keys)->toContain('upgrade');
});

it('excludes upgrade banner when up to date', function () {
    $this->loginAsAdmin();
    seedLatestVersion(core()->version());

    $keys = collect(app(PromoBanner::class)->visibleBanners())->pluck('key');

    expect($keys)->not->toContain('upgrade');
});

it('always includes cloud banner when not dismissed', function () {
    $this->loginAsAdmin();
    seedLatestVersion(core()->version());

    $keys = collect(app(PromoBanner::class)->visibleBanners())->pluck('key');

    expect($keys)->toContain('cloud');
});

it('returns no banners when no admin is authenticated', function () {
    seedLatestVersion('9.9.9');

    expect(app(PromoBanner::class)->visibleBanners())->toBe([]);
});

it('excludes cloud banner after it is dismissed for the admin', function () {
    $admin = $this->loginAsAdmin();
    seedLatestVersion(core()->version());

    app(AdminPromoDismissalRepository::class)->dismiss($admin->id, 'cloud');

    $keys = collect(app(PromoBanner::class)->visibleBanners())->pluck('key');

    expect($keys)->not->toContain('cloud');
});

it('scopes upgrade dismissal to the latest version', function () {
    $admin = $this->loginAsAdmin();
    seedLatestVersion('9.9.9');

    app(AdminPromoDismissalRepository::class)->dismiss($admin->id, 'upgrade', '9.9.9');

    $keys = collect(app(PromoBanner::class)->visibleBanners())->pluck('key');
    expect($keys)->not->toContain('upgrade');

    // A newer release should re-show the upgrade banner.
    seedLatestVersion('10.0.0');

    $keys = collect(app(PromoBanner::class)->visibleBanners())->pluck('key');
    expect($keys)->toContain('upgrade');
});

it('upgrade message contains the current version string', function () {
    $this->loginAsAdmin();
    seedLatestVersion('9.9.9');

    $upgrade = collect(app(PromoBanner::class)->visibleBanners())
        ->firstWhere('key', 'upgrade');

    expect($upgrade)->not->toBeNull();
    expect($upgrade['message'])->toContain(core()->version());
});
