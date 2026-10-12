<?php

use Webkul\ProductPassport\Services\TemplateReadinessGate;
use Webkul\Publication\Registry\PublicationTypeRegistry;

it('registers the dpp publication type', function (): void {
    $type = resolve(PublicationTypeRegistry::class)->get('dpp');

    expect($type->routePrefix)->toBe('p')
        ->and($type->gate)->toBe(TemplateReadinessGate::class)
        ->and($type->template)->toBe('passport::public.passport');
});
