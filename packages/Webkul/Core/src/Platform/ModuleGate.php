<?php

namespace Webkul\Core\Platform;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/**
 * Decides which platform modules are switched on for the current request.
 *
 * Packages register modules under `platform_modules`; a module listing `requires`
 * only runs alongside those modules, the rest run alone or in any combination.
 * Bound scoped: the selection and the key map are read once per request, so a
 * settings change is seen by the next request without a worker restart.
 * A module's `route_prefixes` and ACL routes only gate named routes; an unnamed
 * route is always reachable, so a package names every route it wants gated.
 */
final class ModuleGate
{
    public const string CONFIG_KEY = 'general.platform.settings.modules';

    public const string HIDDEN_SECTIONS_KEY = 'general.platform.settings.hidden_sections';

    /**
     * @var list<string>|null
     */
    private ?array $modules = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $keyModules = null;

    /**
     * @var array<string, array{primary: list<string>, alternates: list<string>}>|null
     */
    private ?array $routeKeys = null;

    /**
     * @var list<string>|null
     */
    private ?array $hidden = null;

    /**
     * @var list<string>|null
     */
    private ?array $hideable = null;

    /**
     * The switched-on module keys.
     *
     * Unregistered keys and modules whose requirements are off are dropped, so a
     * hand-edited value can never run Shopify without PIM; an empty result falls
     * back to `pim`, so an uninstalled module can never switch everything off.
     *
     * @return list<string>
     */
    public function modules(): array
    {
        return $this->modules ??= $this->resolveModules();
    }

    /**
     * Whether the module is switched on.
     */
    public function enabled(Module|string $module): bool
    {
        return in_array($module instanceof Module ? $module->value : $module, $this->modules(), true);
    }

    /**
     * The module keys present in the `platform_modules` registry; PIM is always present and first.
     *
     * @return list<string>
     */
    public function registered(): array
    {
        $modules = [Module::Pim->value];

        foreach (array_keys((array) config('platform_modules', [])) as $key) {
            if (is_string($key) && $key !== '' && ! in_array($key, $modules, true)) {
                $modules[] = $key;
            }
        }

        return $modules;
    }

    /**
     * The module keys a module only runs alongside.
     *
     * @return list<string>
     */
    public function requirements(string $module): array
    {
        return array_values(array_filter(
            (array) config('platform_modules.'.$module.'.requires', []),
            fn (mixed $required): bool => is_string($required) && $required !== '' && $required !== $module,
        ));
    }

    /**
     * Options for the modules setting, labelled from each module's `name`.
     *
     * @return list<array{title: string, value: string}>
     */
    public function moduleOptions(): array
    {
        return array_map(fn (string $module): array => [
            'title' => $this->label($module),
            'value' => $module,
        ], $this->registered());
    }

    /**
     * Translated reasons a module selection cannot be saved; empty when it is valid.
     *
     * @param  list<string>  $modules
     * @return list<string>
     */
    public function selectionErrors(array $modules): array
    {
        if ($modules === []) {
            return [trans('core::validation.platform-modules.empty')];
        }

        $registered = $this->registered();

        $errors = [];

        foreach ($modules as $module) {
            if (! in_array($module, $registered, true)) {
                $errors[] = trans('core::validation.platform-modules.unknown', ['module' => $module]);

                continue;
            }

            $missing = array_diff($this->requirements($module), $modules);

            if ($missing !== []) {
                $errors[] = trans('core::validation.platform-modules.requires', [
                    'module'   => $this->label($module),
                    'requires' => implode(', ', array_map($this->label(...), $missing)),
                ]);
            }
        }

        return $errors;
    }

    /**
     * Split a stored or submitted modules value into unique, lower-cased module keys.
     *
     * @return list<string>
     */
    public static function parseModules(mixed $value): array
    {
        return array_values(array_unique(self::parseSections(is_string($value) ? strtolower($value) : $value)));
    }

    /**
     * The module owning an ACL or menu key, inherited from its longest tagged dot-prefix.
     */
    public function moduleOfKey(string $key): ?string
    {
        $map = $this->keyModules ??= $this->buildKeyMap();

        $segments = explode('.', $key);

        while ($segments !== []) {
            $candidate = implode('.', $segments);

            if (isset($map[$candidate])) {
                return $map[$candidate];
            }

            array_pop($segments);
        }

        return null;
    }

    /**
     * Whether an ACL or menu key is reachable: its module is on and the admin has not hidden its section.
     */
    public function allowsKey(string $key): bool
    {
        $module = $this->moduleOfKey($key);

        if ($module !== null && ! $this->enabled($module)) {
            return false;
        }

        foreach ($this->hiddenSections() as $section) {
            if ($key === $section || str_starts_with($key, $section.'.')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Section keys the admin hid; applies only while PIM is off, where PIM screens have no use.
     *
     * Stored keys not flagged `hideable` are dropped, so a crafted value can never hide
     * System Settings, Users or Roles and lock the admin out. With nothing stored, the
     * sections flagged `hidden_by_default` are hidden.
     *
     * @return list<string>
     */
    public function hiddenSections(): array
    {
        if ($this->hidden !== null) {
            return $this->hidden;
        }

        if ($this->enabled(Module::Pim)) {
            return $this->hidden = [];
        }

        return $this->hidden = array_values(array_intersect(
            $this->hideableSections(),
            self::parseSections(
                core()->getConfigData(self::HIDDEN_SECTIONS_KEY) ?? $this->defaultHiddenSections()
            ),
        ));
    }

    /**
     * Menu keys flagged `hideable` by their package.
     *
     * @return list<string>
     */
    public function hideableSections(): array
    {
        return $this->hideable ??= array_values(array_unique(array_map(
            fn (array $item): string => $item['key'],
            array_filter(
                (array) config('menu.admin', []),
                fn (mixed $item): bool => is_array($item)
                    && ! empty($item['hideable'])
                    && ! empty($item['key'])
                    && is_string($item['key'])
                    && ! isset($item['module']),
            ),
        )));
    }

    /**
     * Comma-separated hideable menu keys whose package flags them `hidden_by_default`.
     */
    public function defaultHiddenSections(): string
    {
        $hideable = $this->hideableSections();

        return implode(',', array_values(array_unique(array_filter(array_map(
            fn (mixed $item): ?string => is_array($item) && ! empty($item['hidden_by_default']) && in_array($item['key'] ?? null, $hideable, true) ? $item['key'] : null,
            (array) config('menu.admin', []),
        )))));
    }

    /**
     * Options for the hidden-sections setting, labelled with the parent menu name.
     *
     * @return list<array{title: string, value: string}>
     */
    public function hideableSectionOptions(): array
    {
        $names = [];

        foreach ((array) config('menu.admin', []) as $item) {
            if (is_array($item) && is_string($item['key'] ?? null) && is_string($item['name'] ?? null)) {
                $names[$item['key']] ??= trans($item['name']);
            }
        }

        return array_map(function (string $key) use ($names): array {
            $parent = str_contains($key, '.') ? ($names[strstr($key, '.', true)] ?? null) : null;

            $name = $names[$key] ?? $key;

            return [
                'title' => $parent !== null ? $parent.' › '.$name : $name,
                'value' => $key,
            ];
        }, $this->hideableSections());
    }

    /**
     * Split a stored or submitted hidden-sections value into section keys.
     *
     * @return list<string>
     */
    public static function parseSections(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn (mixed $section): string => is_string($section) ? trim($section) : '', $value),
            fn (string $section): bool => $section !== '',
        )));
    }

    /**
     * Whether a named route is reachable in the current mode.
     *
     * Route prefixes are checked first so store/update routes without an ACL entry
     * are still gated. Then every ACL key owning the route must be allowed; a route
     * owned only through `also_authorizes` needs one allowed owner. The map is read
     * from raw config, never from the translated `acl` tree, so calling this before
     * the admin locale is set cannot freeze labels in the wrong locale.
     */
    public function allowsRoute(?string $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return true;
        }

        foreach ($this->registered() as $module) {
            if ($this->enabled($module)) {
                continue;
            }

            foreach ((array) config('platform_modules.'.$module.'.route_prefixes', []) as $prefix) {
                if (is_string($prefix) && $prefix !== '' && str_starts_with($routeName, $prefix)) {
                    return false;
                }
            }
        }

        $keys = ($this->routeKeys ??= $this->buildRouteMap())[$routeName] ?? null;

        if ($keys === null) {
            return true;
        }

        foreach ($keys['primary'] as $key) {
            if (! $this->allowsKey($key)) {
                return false;
            }
        }

        if ($keys['primary'] !== [] || $keys['alternates'] === []) {
            return true;
        }

        foreach ($keys['alternates'] as $key) {
            if ($this->allowsKey($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Keep registry items whose `module` is absent or enabled, preserving keys.
     *
     * @param  array<array-key, mixed>  $items
     * @return array<array-key, mixed>
     */
    public function filter(array $items): array
    {
        return array_filter($items, function (mixed $item): bool {
            if (! is_array($item) || ! isset($item['module'])) {
                return true;
            }

            $module = $this->toModuleKey($item['module']);

            return $module !== null && $this->enabled($module);
        });
    }

    /**
     * Recursively drop tree items (`key`, `children`) whose key is not allowed.
     *
     * @param  array<array-key, mixed>  $items
     * @return array<array-key, mixed>
     */
    public function pruneTree(array $items): array
    {
        $pruned = [];

        foreach ($items as $index => $item) {
            if (is_array($item) && isset($item['key']) && is_string($item['key']) && ! $this->allowsKey($item['key'])) {
                continue;
            }

            if (is_array($item) && ! empty($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->pruneTree($item['children']);
            }

            $pruned[$index] = $item;
        }

        return $pruned;
    }

    /**
     * Merge submitted permission keys with the stored keys of switched-off modules.
     *
     * Permission trees hide off-module nodes, so their keys are never submitted;
     * keeping the stored ones stops a save in one mode from wiping another module's
     * rights, and dropping submitted off-module keys stops a crafted request from
     * granting rights the form could not show.
     *
     * @param  array<array-key, mixed>  $submitted
     * @param  array<array-key, mixed>  $stored
     * @return list<string>
     */
    public function keepHiddenKeys(array $submitted, array $stored): array
    {
        $visible = array_filter($submitted, fn (mixed $key): bool => is_string($key) && $this->allowsKey($key));

        $hidden = array_filter($stored, fn (mixed $key): bool => is_string($key) && ! $this->allowsKey($key));

        return array_values(array_unique([...$visible, ...$hidden]));
    }

    /**
     * Before the `core_config` table exists (fresh install, pending migration) or while no
     * database is reachable (`route:list` building controllers in CI) there is no stored
     * selection, so the gate degrades to `pim`; any other query fault is rethrown.
     *
     * @return list<string>
     */
    private function resolveModules(): array
    {
        try {
            $value = core()->getConfigData(self::CONFIG_KEY);
        } catch (QueryException $e) {
            throw_if($this->configTableExists(), $e);

            return [Module::Pim->value];
        }

        $modules = array_values(array_intersect(self::parseModules($value), $this->registered()));

        do {
            $count = count($modules);

            $modules = array_values(array_filter(
                $modules,
                fn (string $module): bool => array_diff($this->requirements($module), $modules) === [],
            ));
        } while (count($modules) !== $count);

        return $modules === [] ? [Module::Pim->value] : $modules;
    }

    private function configTableExists(): bool
    {
        try {
            return Schema::hasTable('core_config');
        } catch (QueryException) {
            return false;
        }
    }

    private function label(string $module): string
    {
        $name = config('platform_modules.'.$module.'.name');

        return is_string($name) && $name !== '' ? trans($name) : $module;
    }

    /**
     * @return array<string, string>
     */
    private function buildKeyMap(): array
    {
        $map = [];

        foreach (['acl', 'menu.admin', 'api-acl'] as $source) {
            foreach ((array) config($source, []) as $item) {
                if (! is_array($item) || empty($item['key']) || ! is_string($item['key']) || ! isset($item['module'])) {
                    continue;
                }

                $module = $this->toModuleKey($item['module']);

                if ($module !== null) {
                    $map[$item['key']] = $module;
                }
            }
        }

        return $map;
    }

    /**
     * @return array<string, array{primary: list<string>, alternates: list<string>}>
     */
    private function buildRouteMap(): array
    {
        $map = [];

        foreach (['acl', 'api-acl'] as $source) {
            foreach ((array) config($source, []) as $item) {
                if (! is_array($item) || empty($item['key']) || ! is_string($item['key'])) {
                    continue;
                }

                if (! empty($item['route']) && is_string($item['route'])) {
                    $map[$item['route']] ??= ['primary' => [], 'alternates' => []];
                    $map[$item['route']]['primary'][] = $item['key'];
                }

                foreach ((array) ($item['also_authorizes'] ?? []) as $route) {
                    if (is_string($route) && $route !== '') {
                        $map[$route] ??= ['primary' => [], 'alternates' => []];
                        $map[$route]['alternates'][] = $item['key'];
                    }
                }
            }
        }

        return $map;
    }

    private function toModuleKey(mixed $value): ?string
    {
        if ($value instanceof Module) {
            return $value->value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
