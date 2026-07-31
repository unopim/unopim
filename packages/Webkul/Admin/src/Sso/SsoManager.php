<?php

namespace Webkul\Admin\Sso;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Webkul\Admin\Contracts\SsoProvider;

class SsoManager
{
    /**
     * @var array<string, SsoProvider>
     */
    protected array $resolved = [];

    public function __construct(protected readonly Container $container) {}

    /**
     * Every registered driver, ordered by sort weight then code.
     *
     * @return Collection<string, SsoProvider>
     */
    public function all(): Collection
    {
        return collect(array_keys(config('sso.providers', [])))
            ->mapWithKeys(fn (string $code): array => [$code => $this->get($code)])
            ->filter()
            ->sortBy([
                fn (SsoProvider $a, SsoProvider $b): int => $a->getSort() <=> $b->getSort(),
                fn (SsoProvider $a, SsoProvider $b): int => $a->getCode() <=> $b->getCode(),
            ]);
    }

    /**
     * Drivers that are switched on and fully credentialed.
     *
     * @return Collection<string, SsoProvider>
     */
    public function enabled(): Collection
    {
        return $this->all()->filter(fn (SsoProvider $provider): bool => $provider->isEnabled());
    }

    public function has(string $code): bool
    {
        return isset(config('sso.providers', [])[$code]);
    }

    /**
     * Resolve a driver by code, or null when it is not registered.
     */
    public function get(string $code): ?SsoProvider
    {
        if (isset($this->resolved[$code])) {
            return $this->resolved[$code];
        }

        $driver = config('sso.providers', [])[$code] ?? null;

        if ($driver === null) {
            return null;
        }

        $provider = is_callable($driver)
            ? $driver($this->container)
            : $this->container->make($driver);

        if (! $provider instanceof SsoProvider) {
            throw new InvalidArgumentException(
                sprintf('SSO provider [%s] must implement %s.', $code, SsoProvider::class)
            );
        }

        return $this->resolved[$code] = $provider;
    }

    /**
     * Resolve a driver that is registered and currently usable.
     */
    public function getEnabled(string $code): ?SsoProvider
    {
        $provider = $this->get($code);

        return $provider?->isEnabled() ? $provider : null;
    }
}
