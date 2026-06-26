<?php

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\HistoryControl\Http\Controllers\HistoryController;

dataset('attribute types', [
    'text', 'textarea', 'price', 'boolean', 'select', 'multiselect',
    'datetime', 'date', 'image', 'gallery', 'file', 'checkbox',
]);

beforeEach(function () {
    $this->repository = app(AttributeRepository::class);
    $this->controller = app(HistoryController::class);
});

it('renders create-version history without crashing for every attribute type', function (string $type) {
    $attribute = $this->repository->create([
        'code'         => 'hist_'.$type.'_'.uniqid(),
        'type'         => $type,
        'ai_translate' => 0,
    ]);

    $response = $this->controller->getVersionHistoryView('attribute', $attribute->id, 1);

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode($response->getContent(), true);

    expect($data['version'])->toBe(1);
    expect($data['versionHistory'])->toHaveKey('type');
    expect($data['versionHistory'])->toHaveKey('code');
    expect($data['versionHistory']['type']['new'])->not->toBe('');
})->with('attribute types');

it('filters empty and falsy-default noise fields for every attribute type', function (string $type) {
    $attribute = $this->repository->create([
        'code'         => 'noise_'.$type.'_'.uniqid(),
        'type'         => $type,
        'ai_translate' => 0,
    ]);

    $data = json_decode(
        $this->controller->getVersionHistoryView('attribute', $attribute->id, 1)->getContent(),
        true
    );

    expect($data['versionHistory'])->not->toHaveKey('validation');
    expect($data['versionHistory'])->not->toHaveKey('regex_pattern');
    expect($data['versionHistory'])->not->toHaveKey('ai_translate');
})->with('attribute types');
