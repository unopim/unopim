<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Core;
use Webkul\Core\Platform\ModuleGate;

function unreachableDatabaseError(): QueryException
{
    return new QueryException('mysql', 'select 1', [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
}

it('degrades to pim when no database is reachable', function () {
    $core = Mockery::mock(Core::class);
    $core->shouldReceive('getConfigData')->andThrow(unreachableDatabaseError());

    app()->instance('core', $core);

    Schema::shouldReceive('hasTable')->with('core_config')->andThrow(unreachableDatabaseError());

    $gate = new ModuleGate;

    expect($gate->modules())->toBe(['pim'])
        ->and($gate->allowsRoute('admin.catalog.products.index'))->toBeTrue();
});

it('rethrows a query fault once the config table exists', function () {
    $core = Mockery::mock(Core::class);
    $core->shouldReceive('getConfigData')->andThrow(unreachableDatabaseError());

    app()->instance('core', $core);

    Schema::shouldReceive('hasTable')->with('core_config')->andReturnTrue();

    (new ModuleGate)->modules();
})->throws(QueryException::class);
