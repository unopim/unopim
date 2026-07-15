<?php

use Webkul\Resource\Support\ResourceRegistry;

it('boots the package: registry singleton, views and translations registered', function () {
    expect(app(ResourceRegistry::class))->toBeInstanceOf(ResourceRegistry::class);
    expect(view()->exists('resource::index'))->toBeTrue();
    expect(view()->exists('resource::edit'))->toBeTrue();
    expect(trans('resource::app.create-success'))->not->toBe('resource::app.create-success');
});
