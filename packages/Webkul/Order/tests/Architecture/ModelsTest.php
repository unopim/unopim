<?php

use Illuminate\Database\Eloquent\Model;
use Webkul\Order\Contracts;
use Webkul\Order\Models\OrderSyncLog;
use Webkul\Order\Models\OrderWebhook;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;
use Webkul\Tenant\Traits\BelongsToTenant;

arch('models extend base model')
    ->expect('Webkul\Order\Models')
    ->toExtend(Model::class);

arch('models use proper traits')
    ->expect([
        UnifiedOrder::class,
        UnifiedOrderItem::class,
        OrderSyncLog::class,
        OrderWebhook::class,
    ])
    ->toUse(BelongsToTenant::class);

arch('models have proper table names')
    ->expect('Webkul\Order\Models')
    ->toHaveProperty('table');

arch('models implement contracts')
    ->expect([
        [UnifiedOrder::class, Contracts\UnifiedOrder::class],
        [UnifiedOrderItem::class, Contracts\UnifiedOrderItem::class],
        [OrderSyncLog::class, Contracts\OrderSyncLog::class],
        [OrderWebhook::class, Contracts\OrderWebhook::class],
    ])
    ->each(fn ($pair) => expect($pair[0])->toImplement($pair[1]));

arch('models have fillable properties')
    ->expect('Webkul\Order\Models')
    ->toHaveProperty('fillable')
    ->each(fn ($class) => expect($class->fillable)->toBeArray());

arch('models have casts defined')
    ->expect('Webkul\Order\Models')
    ->toHaveMethod('casts');

arch('models are in correct namespace')
    ->expect('Webkul\Order\Models')
    ->toBeClasses()
    ->not->toBeAbstract()
    ->not->toBeInterfaces();

arch('models do not use die or dd')
    ->expect('Webkul\Order\Models')
    ->not->toUse(['die', 'dd', 'dump', 'var_dump']);
