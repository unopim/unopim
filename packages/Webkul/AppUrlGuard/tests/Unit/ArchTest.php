<?php

/**
 * Architecture guards for the package — cheap, fast invariants that keep the
 * code honest as it evolves.
 */
arch('ships no debugging statements')
    ->expect('Webkul\AppUrlGuard')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die']);

arch('controllers are suffixed correctly')
    ->expect('Webkul\AppUrlGuard\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('middleware exposes a handle method')
    ->expect('Webkul\AppUrlGuard\Http\Middleware')
    ->toHaveMethod('handle');

arch('the provider extends the framework service provider')
    ->expect('Webkul\AppUrlGuard\Providers\AppUrlGuardServiceProvider')
    ->toExtend('Illuminate\Support\ServiceProvider');
