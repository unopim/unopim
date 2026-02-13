<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

it('injects tenant_id WHERE clause into DataGrid query builder', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    // Create a simple query builder simulating a DataGrid
    $queryBuilder = DB::table('products')->select('products.id', 'products.sku');

    // Instantiate a test DataGrid and verify tenant scope is applied
    $dataGrid = new class extends \Webkul\DataGrid\DataGrid
    {
        public function prepareQueryBuilder()
        {
            return DB::table('products')->select('products.id', 'products.sku');
        }

        public function prepareColumns()
        {
        }
    };

    $dataGrid->setQueryBuilder();

    // Get the query and verify tenant_id is in the WHERE clause
    $reflection = new \ReflectionProperty($dataGrid, 'queryBuilder');
    $reflection->setAccessible(true);
    $builder = $reflection->getValue($dataGrid);

    $query = $builder instanceof \Illuminate\Database\Eloquent\Builder
        ? $builder->getQuery()
        : $builder;

    $sql = $query->toSql();
    expect($sql)->toContain('tenant_id');

    core()->setCurrentTenantId(null);
});

it('skips tenant scope when no tenant context', function () {
    core()->setCurrentTenantId(null);

    $dataGrid = new class extends \Webkul\DataGrid\DataGrid
    {
        public function prepareQueryBuilder()
        {
            return DB::table('products')->select('products.id', 'products.sku');
        }

        public function prepareColumns()
        {
        }
    };

    $dataGrid->setQueryBuilder();

    $reflection = new \ReflectionProperty($dataGrid, 'queryBuilder');
    $reflection->setAccessible(true);
    $builder = $reflection->getValue($dataGrid);

    $query = $builder instanceof \Illuminate\Database\Eloquent\Builder
        ? $builder->getQuery()
        : $builder;

    $sql = $query->toSql();
    expect($sql)->not->toContain('tenant_id');
});

it('handles aliased table names correctly', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $dataGrid = new class extends \Webkul\DataGrid\DataGrid
    {
        public function prepareQueryBuilder()
        {
            return DB::table('products as p')->select('p.id', 'p.sku');
        }

        public function prepareColumns()
        {
        }
    };

    $dataGrid->setQueryBuilder();

    $reflection = new \ReflectionProperty($dataGrid, 'queryBuilder');
    $reflection->setAccessible(true);
    $builder = $reflection->getValue($dataGrid);

    $sql = $builder->toSql();
    // SQLite quotes identifiers: "p"."tenant_id" — check for tenant_id presence
    expect($sql)->toContain('tenant_id');

    core()->setCurrentTenantId(null);
});
