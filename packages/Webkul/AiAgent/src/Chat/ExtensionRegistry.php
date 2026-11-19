<?php

namespace Webkul\AiAgent\Chat;

use InvalidArgumentException;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Registration boundary for trusted, installed extensions.
 *
 * Providers, views and components come from service providers, never HTTP input.
 * Registration supplies integration points, not permission to access a resource;
 * each extension must authorize its requests.
 *
 * @phpstan-type ProviderOption array{label: string, id?: int, provider?: string, models?: list<string>, is_default?: bool, hide_single_model?: bool}
 * @phpstan-type ComposerRegistration array{key: string, component: string, view: string}
 * @phpstan-type CapabilityPresentation array{description?: string, extension_event?: string|null}
 */
class ExtensionRegistry
{
    public const COMPOSER_REFERENCES = 2;

    /** Capability fields an extension may override. */
    private const CAPABILITY_OVERRIDES = ['description', 'extension_event'];

    /** Namespace pattern shared by composer keys and the chat request's extension selections. */
    public const NAMESPACE_PATTERN = '/^[a-z][a-z0-9._-]{0,63}$/D';

    /** @var array<int, array{resolve: callable(): ?MagicAIPlatform, option: ProviderOption}> */
    private array $providers = [];

    /** @var array<string, ComposerRegistration> */
    private array $composers = [];

    /** @var array<string, CapabilityPresentation> */
    private array $capabilities = [];

    /**
     * Register a lazy provider; the negative ID overrides any ID in its display options.
     *
     * @param  callable(): ?MagicAIPlatform  $resolve
     * @param  ProviderOption  $option
     */
    public function provider(int $id, callable $resolve, array $option): void
    {
        if ($id >= 0) {
            throw new InvalidArgumentException('Extension provider IDs must be negative.');
        }

        $this->providers[$id] = ['resolve' => $resolve, 'option' => ['id' => $id] + $option];
    }

    /** Resolve an installed provider, or return null when it is unavailable. */
    public function resolve(int $id): ?MagicAIPlatform
    {
        return isset($this->providers[$id]) ? ($this->providers[$id]['resolve'])() : null;
    }

    /** @return list<ProviderOption> */
    public function options(): array
    {
        return array_values(array_map(fn ($entry) => $entry['option'], $this->providers));
    }

    /** Register trusted component/view names under a validated composer key. */
    public function composer(string $key, string $component, string $view): void
    {
        if (! preg_match(self::NAMESPACE_PATTERN, $key)
            || ! preg_match('/^v-[a-z0-9-]{1,125}$/D', $component)) {
            throw new InvalidArgumentException('Invalid composer registration.');
        }

        $this->composers[$key] = compact('key', 'component', 'view');
    }

    /** @return list<ComposerRegistration> */
    public function composers(): array
    {
        return array_values($this->composers);
    }

    /**
     * Override how a built-in capability is presented; keys other than
     * description and extension_event are dropped.
     *
     * @param  CapabilityPresentation  $presentation
     */
    public function capability(string $key, array $presentation): void
    {
        if (! preg_match(self::NAMESPACE_PATTERN, $key)) {
            throw new InvalidArgumentException('Invalid capability registration.');
        }

        $this->capabilities[$key] = array_intersect_key($presentation, array_flip(self::CAPABILITY_OVERRIDES));
    }

    /** @return array<string, CapabilityPresentation> */
    public function capabilities(): array
    {
        return $this->capabilities;
    }
}
