<?php

namespace Webkul\Core;

use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;

/** Per-request catalog locale and channel. Bind with `scoped()`, never `singleton()` — Octane leaks it. */
class CatalogScope
{
    public function __construct(
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository
    ) {}

    /** Request parameter, else the admin's active catalog locale, else the channel default. Never memoize. */
    public function localeCode(): string
    {
        return $this->resolveLocaleCode();
    }

    /** Request parameter, else the admin's default channel, else the configured one. Never memoize. */
    public function channelCode(): ?string
    {
        return $this->resolveChannelCode();
    }

    protected function resolveLocaleCode(): string
    {
        $requested = request()->input('locale');

        if (core()->isValidScopeCode($requested)) {
            return $requested;
        }

        $catalogLocale = $this->admin()?->catalogLocale;

        if ($catalogLocale?->status) {
            return $catalogLocale->code;
        }

        return core()->getDefaultLocaleCodeFromDefaultChannel();
    }

    protected function resolveChannelCode(): ?string
    {
        $requested = request()->input('channel');

        if (core()->isValidScopeCode($requested)) {
            return $requested;
        }

        return $this->admin()?->defaultChannel?->code
            ?? core()->getDefaultChannelCode();
    }

    /** The authenticated admin, or null in CLI, queue, API and importer contexts. */
    protected function admin()
    {
        if (! app()->bound('auth')) {
            return null;
        }

        return auth()->guard('admin')->user();
    }
}
