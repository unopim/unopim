<?php

namespace Webkul\ProductPassport\Tests;

use Tests\TestCase;
use Webkul\User\Tests\Concerns\UserAssertions;

/**
 * Mirrors `Webkul\Publication\Tests\PublicationTestCase`'s structure. Tasks
 * 9-10 add their own fixture helpers here (`productWithSecretAndDppAttributes()`,
 * `variantWithInheritedPassportValues()`, `setPassportConfig()`).
 */
class ProductPassportTestCase extends TestCase
{
    use UserAssertions;
}
