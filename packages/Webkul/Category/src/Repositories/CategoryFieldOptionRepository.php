<?php

namespace Webkul\Category\Repositories;

use Webkul\Category\Contracts\CategoryFieldOption;
use Webkul\Core\Eloquent\Repository;

class CategoryFieldOptionRepository extends Repository
{
    /**
     * This method returns the class name of the model associated with this repository.
     */
    public function model(): string
    {
        return CategoryFieldOption::class;
    }
}
