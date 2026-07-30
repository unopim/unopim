<?php

use Webkul\Publication\Registry\PublicationTypeRegistry;

beforeEach(function (): void {
    config()->set('publication.types.demo', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => 'Demo\\Builder',
        'template'        => 'demo::page',
        'route_prefix'    => 'd',
        'gate'            => 'Demo\\Gate',
    ]);
});

it('resolves a registered type', function (): void {
    $type = resolve(PublicationTypeRegistry::class)->get('demo');

    expect($type->code)->toBe('demo')
        ->and($type->routePrefix)->toBe('d')
        ->and($type->gate)->toBe('Demo\\Gate');
});

it('rejects an unknown type', function (): void {
    expect(fn () => resolve(PublicationTypeRegistry::class)->get('nope'))
        ->toThrow(InvalidArgumentException::class);
});
