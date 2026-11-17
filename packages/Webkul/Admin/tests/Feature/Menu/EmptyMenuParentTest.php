<?php

use Webkul\Admin\Providers\AdminServiceProvider;

function menuParentProbe(): AdminServiceProvider
{
    return new class(app()) extends AdminServiceProvider
    {
        public function probe(string $key, array $items, array $allowedKeys, array $visibleKeys): bool
        {
            return $this->lostAllChildren($key, $items, $allowedKeys, $visibleKeys);
        }
    };
}

it('keeps a parent whose children were only removed by permissions', function () {
    $items = [['key' => 'connector'], ['key' => 'connector.credentials'], ['key' => 'connector.mappings']];

    $visibleKeys = ['connector', 'connector.credentials', 'connector.mappings'];

    expect(menuParentProbe()->probe('connector', $items, ['connector'], $visibleKeys))->toBeFalse();
});

it('drops a parent once the module gate removed its children and none is left', function () {
    $items = [['key' => 'catalog'], ['key' => 'catalog.products'], ['key' => 'catalog.attributes']];

    expect(menuParentProbe()->probe('catalog', $items, ['catalog'], ['catalog', 'catalog.attributes']))->toBeTrue()
        ->and(menuParentProbe()->probe('catalog', $items, ['catalog', 'catalog.attributes'], ['catalog', 'catalog.attributes']))->toBeFalse();
});
