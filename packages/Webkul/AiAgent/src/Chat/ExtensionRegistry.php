<?php

namespace Webkul\AiAgent\Chat;

use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Registration boundary for trusted, installed extensions.
 *
 * Providers, views and components come from service providers, never HTTP input.
 * Registration supplies integration points, not permission to access a resource;
 * each extension must authorize its requests.
 */
class ExtensionRegistry
{
    public const COMPOSER_REFERENCES = 2;

    private array $providers = [];

    private array $composers = [];

    private array $capabilities = [];

    public function provider(int $id, callable $resolve, array $option): void
    {
        if ($id >= 0) {
            throw new \InvalidArgumentException('Extension provider IDs must be negative.');
        }
        $this->providers[$id] = ['resolve' => $resolve, 'option' => ['id' => $id] + $option];
    }

    public function resolve(int $id): ?MagicAIPlatform
    {
        return isset($this->providers[$id]) ? ($this->providers[$id]['resolve'])() : null;
    }

    public function options(): array
    {
        return array_values(array_map(fn ($entry) => $entry['option'], $this->providers));
    }

    public function composer(string $key, string $component, string $view): void
    {
        if (! preg_match('/^[a-z][a-z0-9._-]{0,63}$/D', $key)
            || ! preg_match('/^v-[a-z0-9-]{1,125}$/D', $component)) {
            throw new \InvalidArgumentException('Invalid composer registration.');
        }

        $this->composers[$key] = compact('key', 'component', 'view');
    }

    public function composers(): array
    {
        return array_values($this->composers);
    }

    public function capability(string $key, array $presentation): void
    {
        $this->capabilities[$key] = $presentation;
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }
}
