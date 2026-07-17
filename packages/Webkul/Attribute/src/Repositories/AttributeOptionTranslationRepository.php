<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Attribute\Contracts\AttributeOptionTranslation;
use Webkul\Core\Eloquent\Repository;

class AttributeOptionTranslationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeOptionTranslation::class;
    }
}
