<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Support\Arr;
use Webkul\Core\Eloquent\Repository;

class AttributeGroupRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeGroup';
    }

    /**
     * This function returns a query builder instance for the AttributeGroup model.
     * It eager loads the 'translations' relationship for the attribute groups.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryBuilder()
    {
        return $this->with(['translations']);
    }
}
