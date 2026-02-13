<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->tenantInfrastructureExists()) {
            core()->setCurrentTenantId(1);
        }
    }

    protected function tearDown(): void
    {
        if ($this->tenantInfrastructureExists()) {
            core()->setCurrentTenantId(null);
        }

        parent::tearDown();
    }

    private function tenantInfrastructureExists(): bool
    {
        try {
            return Schema::hasTable('tenants')
                && \DB::table('tenants')->where('id', 1)->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
