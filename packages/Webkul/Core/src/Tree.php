<?php

namespace Webkul\Core;

use Illuminate\Support\Facades\Request;

class Tree
{
    /**
     * Contains tree item
     *
     * @var array<int|string, mixed>
     */
    public array $items = [];

    /**
     * Contains acl roles
     *
     * @var array
     */
    public $roles = [];

    /**
     * Contains current item route
     *
     * @var string
     */
    public $current;

    /**
     * Contains current item key
     *
     * @var string
     */
    public $currentKey;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->current = Request::url();
    }

    /**
     * Shortcut method for create a Config with a callback.
     * This will allow you to do things like fire an event on creation.
     *
     * @param  callable|null  $callback  Callback to use after the Config creation
     */
    public static function create($callback = null): self
    {
        $tree = new Tree;

        if ($callback) {
            $callback($tree);
        }

        return $tree;
    }

    /**
     * Add a Config item to the item stack
     *
     * @param  array<string, mixed>  $item
     */
    public function add(array $item, $type = ''): void
    {
        $item['children'] = [];

        if ($type == 'menu') {
            $item['url'] = route($item['route'], $item['params'] ?? []);

            if ($this->isCurrentUrl($item['url'])) {
                $this->currentKey = $item['key'];
            }
        } elseif ($type == 'acl') {
            $item['name'] = trans($item['name']);

            /**
             * A routeless acl entry registers only as an assignable permission
             * checkbox; enforcement happens in-controller (e.g. per-section
             * System Settings rows that all share one generic editor route).
             */
            if (! empty($item['route'])) {
                $this->roles[$item['route']] = $item['key'];
            }
        }

        $children = str_replace('.', '.children.', $item['key']);

        core()->array_set($this->items, $children, $item);
    }

    /**
     * Method to find the active links
     */
    public function getActive(array $item): ?bool
    {
        if (
            $this->isCurrentUrl($item['url'])
            || $this->isCurrentKeyDescendant($item['key'])
        ) {
            return true;
        }

        return null;
    }

    /**
     * Whether the given menu url matches the current request url, respecting
     * path-segment boundaries so a shorter url (e.g. `configuration/system`)
     * does not match a sibling that merely shares its prefix
     * (e.g. `configuration/system-information`).
     */
    private function isCurrentUrl(string $url): bool
    {
        $current = rtrim($this->current, '/');
        $url = rtrim($url, '/');

        return $current === $url || str_starts_with($current, $url.'/');
    }

    /**
     * Whether the current active key is the given key or one of its
     * descendants, matching on the dotted key hierarchy boundary so a key is
     * not treated as an ancestor of a sibling sharing its prefix.
     */
    private function isCurrentKeyDescendant(string $key): bool
    {
        return $this->currentKey === $key || str_starts_with((string) $this->currentKey, $key.'.');
    }

    /**
     * Build the active menu trail from the top level down to the deepest active
     * item, following the active branch at each level. Used to render breadcrumbs
     * for the current page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveTrail(): array
    {
        $trail = [];

        $nodes = $this->items;

        while (! empty($nodes)) {
            $activeItem = collect($nodes)->first(fn (array $item): bool => (bool) $this->getActive($item));

            if (! $activeItem) {
                break;
            }

            $trail[] = $activeItem;

            $nodes = $activeItem['children'] ?? [];
        }

        return $trail;
    }

    /**
     * Remove unauthorized urls.
     */
    public function removeUnauthorizedUrls(): array
    {
        return collect($this->items)->map(function (array $item): array {
            $this->removeChildrenUnauthorizedUrls($item);

            return $item;
        })->toArray();
    }

    /**
     * Remove all children unauthorized urls. This will handle all levels.
     */
    private function removeChildrenUnauthorizedUrls(array &$item): void
    {
        if (! empty($item['children'])) {
            $firstChildrenItem = collect($item['children'])->first();

            $item['route'] = $firstChildrenItem['route'];

            $item['url'] = route($firstChildrenItem['route'], $firstChildrenItem['params'] ?? []);

            $this->removeChildrenUnauthorizedUrls($firstChildrenItem);
        }
    }
}
