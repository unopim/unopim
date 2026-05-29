<?php

declare(strict_types=1);

namespace Webkul\Attribute\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Attribute\Contracts\AttributeGroup;
use Webkul\Core\Eloquent\Repository;

class AttributeGroupRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeGroup::class;
    }

    /**
     * This function returns a query builder instance for the AttributeGroup model.
     * It eager loads the 'translations' relationship for the attribute groups.
     */
    public function queryBuilder(): static
    {
        return $this->with(['translations']);
    }
}
