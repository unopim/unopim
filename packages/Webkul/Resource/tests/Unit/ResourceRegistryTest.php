<?php

use Webkul\Resource\Support\ResourceRegistry;
use Webkul\Resource\Tests\Fixtures\FakeResource;

it('registers and resolves a resource by name via the container', function () {
    $registry = new ResourceRegistry(app());
    $registry->register('fakes', FakeResource::class);

    expect($registry->has('fakes'))->toBeTrue();
    expect($registry->get('fakes'))->toBeInstanceOf(FakeResource::class);
    expect($registry->all())->toHaveKey('fakes');
});
