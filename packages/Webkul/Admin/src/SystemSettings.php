<?php

namespace Webkul\Admin;

use Webkul\Core\Tree;

class SystemSettings
{
    /**
     * Memoised ACL-filtered tree, built once per request instance. The class is
     * resolved per request (never a singleton), so the per-admin ACL result never
     * leaks across requests under Octane.
     */
    protected ?Tree $tree = null;

    /**
     * Build the ACL-filtered settings tree from config('system_settings').
     */
    public function tree(): Tree
    {
        if ($this->tree !== null) {
            return $this->tree;
        }

        $tree = Tree::create();

        foreach ($this->accessibleItems() as $item) {
            $tree->add($item);
        }

        $tree->items = core()->sortItems($tree->items);

        return $this->tree = $tree;
    }

    /**
     * Find a raw registry entry by its dot key.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        foreach (config('system_settings', []) as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Resolve the field group a `fields` row edits. A row may either declare its
     * fields inline (`fields`) or reference an existing `config('core')` group by
     * key (`config_group`) — the latter keeps the group's config codes intact, so
     * migrating an existing settings group into the hub never relocates saved data.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    public function formGroup(array $entry): ?array
    {
        if (! empty($entry['config_group'])) {
            return collect(config('core'))->firstWhere('key', $entry['config_group']);
        }

        if (! empty($entry['fields'])) {
            return $entry;
        }

        return null;
    }

    /**
     * Registry rows the current admin may see. Rows carrying an `acl` the admin
     * lacks are dropped; sections and acl-less rows always pass.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function accessibleItems(): array
    {
        return array_values(array_filter(config('system_settings', []), function (array $item): bool {
            if (empty($item['acl'])) {
                return true;
            }

            return bouncer()->hasPermission($item['acl']);
        }));
    }
}
