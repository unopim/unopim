<?php

arch('services are in Services namespace')
    ->expect('Webkul\Order\Services')
    ->toBeClasses()
    ->not->toBeAbstract();

arch('services do not use die or dd')
    ->expect('Webkul\Order\Services')
    ->not->toUse(['die', 'dd', 'dump', 'var_dump']);

arch('services use dependency injection')
    ->expect('Webkul\Order\Services')
    ->toHaveMethod('__construct');

arch('services implement contracts where applicable')
    ->expect('Webkul\Order\Services')
    ->toBeClasses();

arch('services use repositories not models directly')
    ->expect('Webkul\Order\Services')
    ->not->toUse([
        'Webkul\Order\Models\UnifiedOrder::create',
        'Webkul\Order\Models\UnifiedOrderItem::create',
    ]);
