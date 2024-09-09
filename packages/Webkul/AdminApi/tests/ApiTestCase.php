<?php

namespace Webkul\AdminApi\Tests;

use Tests\TestCase;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Core\Tests\Concerns\CoreAssertions;

class ApiTestCase extends TestCase
{
    use ApiHelperTrait, CoreAssertions;
}
