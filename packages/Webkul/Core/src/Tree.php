<?php

namespace Webkul\Core;

use Illuminate\Support\Facades\Request;

class Tree
{
    /**
     * Contains tree item
     */
    public array $items = [];

    /**
     * Contains acl roles
     */
    public array $roles = [];

    /**
     * Contains current item route
     */
    public string $current;

    /**
     * Contains current item key
     *
     * @var string
     */
    public mixed $currentKey = null;

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
     * @param  callable  $callback  Callback to use after the Config creation
     * @return object
     */
    public static function create(?callable $callback = null): self
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
     * @param  string  $item
     */
    public function add(array $item, string $type = ''): void
    {
        $item['children'] = [];

        if ($type === 'menu') {
            $item['url'] = route($item['route'], $item['params'] ?? []);

            if (str_contains($this->current, $item['url'])) {
                $this->currentKey = $item['key'];
            }
        } elseif ($type === 'acl') {
            $item['name'] = trans($item['name']);

            $this->roles[$item['route']] = $item['key'];
        }

        $children = str_replace('.', '.children.', $item['key']);

        core()->array_set($this->items, $children, $item);
    }

    /**
     * Method to find the active links
     *
     * @return string|null
     */
    public function getActive(array $item): ?bool
    {
        $url = trim((string) $item['url'], '/');

        if (
            str_contains($this->current, $url)
            || (str_starts_with($this->currentKey, (string) $item['key']))
        ) {
            return true;
        }

        return null;
    }

    /**
     * Remove unauthorized urls.
     */
    public function removeUnauthorizedUrls(): array
    {
        return collect($this->items)->map(function (mixed $item) {
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
