<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Attribute\Contracts\AttributeColumnTranslation;
use Webkul\Core\Eloquent\Repository;

class AttributeColumnTranslationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeColumnTranslation::class;
    }
}
