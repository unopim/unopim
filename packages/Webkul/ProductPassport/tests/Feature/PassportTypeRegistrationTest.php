<?php

use Webkul\Publication\Registry\PublicationTypeRegistry;

it('registers the dpp publication type', function (): void {
    $type = resolve(PublicationTypeRegistry::class)->get('dpp');

    expect($type->routePrefix)->toBe('p')
        ->and($type->requiredGroup)->toBe('dpp')
        ->and($type->template)->toBe('passport::public.passport');
});
