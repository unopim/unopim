<?php

namespace Webkul\Product\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\VariantStructure;

class VariantStructureRepository extends Repository
{
    public function model(): string
    {
        return VariantStructure::class;
    }
}
