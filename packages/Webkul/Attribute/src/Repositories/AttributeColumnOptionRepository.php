<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Attribute\Contracts\AttributeColumnOption;
use Webkul\Core\Eloquent\Repository;

class AttributeColumnOptionRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeColumnOption::class;
    }
}
