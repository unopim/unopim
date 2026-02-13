<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Story 8.1: Wave 5 Migration — Completeness Tables
|--------------------------------------------------------------------------
|
| Verifies that tenant_id column exists on completeness tables after migration.
|
*/

it('has tenant_id on completeness_settings table', function () {
    expect(Schema::hasColumn('completeness_settings', 'tenant_id'))->toBeTrue();
});

it('has tenant_id on product_completeness table', function () {
    expect(Schema::hasColumn('product_completeness', 'tenant_id'))->toBeTrue();
});
