<?php

namespace Webkul\Core;

use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;

/**
 * The locale and channel catalog content is read and written in for the current request.
 *
 * Bound with `scoped()`, never `singleton()`: this stack runs Octane, where a singleton outlives
 * the request and would answer the next admin with the previous admin's locale.
 *
 * Resolution never *requires* an authenticated admin. Core is called from importers, queue jobs,
 * the REST API and CLI commands, where there is no admin session — there, the user step is skipped
 * and the channel/config defaults are used.
 */
class CatalogScope
{
    public function __construct(
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository
    ) {}

    /**
     * Locale for this request: explicit request parameter, else the admin's catalog locale (when it
     * is active), else the default channel's first locale, else the configured application locale.
     *
     * Resolved on every call, never memoized: this is invoked during provider boot (before routing
     * and auth middleware run) as well as later in the request once the admin is authenticated, and
     * those two calls must be allowed to answer differently. The underlying lookups are already
     * cheap and cached elsewhere (the auth guard caches the user; the `catalogLocale`/`defaultChannel`
     * relations are cached on the model instance; `Core::getDefaultChannel()` is memoized).
     */
    public function localeCode(): string
    {
        return $this->resolveLocaleCode();
    }

    /**
     * Channel for this request: explicit request parameter, else the admin's default channel, else
     * the default channel.
     *
     * Resolved on every call — see the note on {@see localeCode()} for why this must not memoize.
     */
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

        return $this->defaultChannelLocaleCode() ?? config('app.locale');
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

    /**
     * First locale attached to the default channel, if any.
     */
    protected function defaultChannelLocaleCode(): ?string
    {
        return core()->getDefaultChannel()?->locales->first()?->code;
    }

    /**
     * The authenticated admin, or null in CLI, queue, API and importer contexts.
     */
    protected function admin()
    {
        if (! app()->bound('auth')) {
            return null;
        }

        return auth()->guard('admin')->user();
    }
}
