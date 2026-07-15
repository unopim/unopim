<?php

namespace Webkul\Resource\Tests\Fixtures;

use Webkul\Core\Eloquent\Repository;

class TestRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return TestModel::class;
    }
}
