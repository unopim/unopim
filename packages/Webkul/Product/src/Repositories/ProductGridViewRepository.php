<?php

namespace Webkul\Product\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\ProductGridView;

class ProductGridViewRepository extends Repository
{
    const DEFAULT_LIMIT = 50;

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ProductGridView::class;
    }

    /**
     * Views the given admin may apply: their own plus everything shared.
     */
    public function visibleTo(int $adminId, string $search = '', int $limit = self::DEFAULT_LIMIT): Collection
    {
        $query = $this->getModel()->newQuery()
            ->where(fn ($builder) => $builder->where('admin_id', $adminId)->orWhere('is_shared', true));

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        return $query->orderBy('name')->limit($limit)->get();
    }
}
