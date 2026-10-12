<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Attribute\Contracts\AttributeFamilyGroupMapping;
use Webkul\Core\Eloquent\Repository;

class AttributeFamilyGroupMappingRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeFamilyGroupMapping::class;
    }
}
