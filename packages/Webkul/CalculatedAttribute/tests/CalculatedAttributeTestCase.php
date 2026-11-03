<?php

namespace Webkul\CalculatedAttribute\Tests;

use Tests\TestCase;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\User\Tests\Concerns\UserAssertions;

/**
 * The feature tests exercise the admin form and the REST API side by side, so
 * both sets of helpers are needed. Both define the same table-prefix helper;
 * either copy will do, so the conflict is resolved rather than worked around.
 */
class CalculatedAttributeTestCase extends TestCase
{
    use ApiHelperTrait, UserAssertions {
        ApiHelperTrait::getFullTableName insteadof UserAssertions;
    }
}
