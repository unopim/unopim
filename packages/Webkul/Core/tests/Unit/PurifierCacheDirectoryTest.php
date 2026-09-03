<?php

use Webkul\Core\Providers\CoreServiceProvider;

it('creates the purifier cache directory on boot', function (): void {
    expect(is_dir(storage_path('app/purifier')))->toBeTrue();
});

it('tolerates a concurrently created purifier cache directory', function (): void {
    (new CoreServiceProvider($this->app))->boot();

    expect(is_dir(storage_path('app/purifier')))->toBeTrue();
});
