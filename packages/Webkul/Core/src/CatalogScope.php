<?php

namespace Webkul\Core;

use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;

class CatalogScope
{
    public function __construct(
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository
    ) {}

    public function localeCode(): string
    {
        return $this->resolveLocaleCode();
    }

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

    protected function admin()
    {
        if (! app()->bound('auth')) {
            return null;
        }

        return auth()->guard('admin')->user();
    }
}
