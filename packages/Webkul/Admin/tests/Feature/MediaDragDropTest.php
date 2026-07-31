<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;

it('renders drag and drop handlers for every media component', function (string $component, int $expectedHandlers) {
    $this->loginAsAdmin();

    $html = Blade::render(
        "<x-admin::media.{$component} name=\"test\" :errors=\"\$errors\" />@stack('scripts')",
        ['errors' => new MessageBag]
    );

    expect(substr_count($html, '@drop.prevent="onDrop"'))->toBe($expectedHandlers);
    expect($html)->toContain('onDrop(event)');
    expect($html)->toContain('isDragging');
})->with([
    'files'   => ['files', 1],
    'gallery' => ['gallery', 1],
    'image'   => ['image', 1],
]);
