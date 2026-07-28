<?php

namespace Webkul\Product\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\AssociationTypeFieldOption;

class AssociationTypeFieldOptionRepository extends Repository
{
    /**
     * This method returns the class name of the model associated with this repository.
     */
    public function model(): string
    {
        return AssociationTypeFieldOption::class;
    }
}
