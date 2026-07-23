<?php

namespace Webkul\Resource\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Tests\TestCase;
use Webkul\Core\Tests\Concerns\CoreAssertions;
use Webkul\Resource\Tests\Fixtures\TestServiceProvider;
use Webkul\User\Tests\Concerns\UserAssertions;

class ResourceTestCase extends TestCase
{
    use CoreAssertions, UserAssertions {
        CoreAssertions::assertModelWise insteadof UserAssertions;
        UserAssertions::assertModelWise as userAssertModelWise;
    }

    /**
     * Registers the fixture's TestServiceProvider before the kernel boots,
     * so its one-time DDL runs outside DatabaseTransactions' per-test transaction.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../../../../bootstrap/app.php';

        $app->register(TestServiceProvider::class);

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
