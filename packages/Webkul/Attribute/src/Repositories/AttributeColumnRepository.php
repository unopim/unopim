<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Attribute\Contracts\AttributeColumn;
use Webkul\Core\Eloquent\Repository;

class AttributeColumnRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeColumn::class;
    }
}
