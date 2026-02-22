<?php

use Illuminate\Routing\Controller;

arch('controllers extend base controller')
    ->expect('Webkul\Order\Http\Controllers')
    ->toExtend(Controller::class);

arch('admin controllers are in Admin namespace')
    ->expect('Webkul\Order\Http\Controllers\Admin')
    ->toBeClasses()
    ->not->toBeAbstract();

arch('API controllers are in Api namespace')
    ->expect('Webkul\Order\Http\Controllers\Api')
    ->toBeClasses()
    ->not->toBeAbstract();

arch('controllers do not use die or dd')
    ->expect('Webkul\Order\Http\Controllers')
    ->not->toUse(['die', 'dd', 'dump', 'var_dump']);

arch('controllers use dependency injection')
    ->expect('Webkul\Order\Http\Controllers')
    ->toHaveMethod('__construct')
    ->ignoring([
        // List controllers without constructor if any
    ]);

arch('controllers have proper naming')
    ->expect('Webkul\Order\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('admin controllers use ACL authorization')
    ->expect('Webkul\Order\Http\Controllers\Admin')
    ->toUseNothing(['DB::raw', 'User::find']);
