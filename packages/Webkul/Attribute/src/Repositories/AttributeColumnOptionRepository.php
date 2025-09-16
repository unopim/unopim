<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Core\Eloquent\Repository;

class AttributeColumnOptionRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeColumnOption';
    }
}
