<?php

use Illuminate\Support\Facades\DB;
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

/**
 * Assigns version_id 1 to the freshly created attribute's audit rows.
 *
 * In production the audits `version_id` is populated by a BEFORE INSERT
 * database trigger. Under DatabaseTransactions the test must not depend on
 * that side effect, so we replicate the trigger's outcome explicitly to keep
 * the assertions deterministic across environments.
 */
function assignCreateVersion(int $attributeId): void
{
    DB::table('audits')
        ->where('tags', 'attribute')
        ->where('history_id', $attributeId)
        ->update(['version_id' => 1]);
}

it('renders create-version history without crashing for every attribute type', function (string $type) {
    $attribute = $this->repository->create([
        'code'         => 'hist_'.$type.'_'.uniqid(),
        'type'         => $type,
        'ai_translate' => 0,
    ]);

    assignCreateVersion($attribute->id);

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

    assignCreateVersion($attribute->id);

    $data = json_decode(
        $this->controller->getVersionHistoryView('attribute', $attribute->id, 1)->getContent(),
        true
    );

    expect($data['versionHistory'])->not->toHaveKey('validation');
    expect($data['versionHistory'])->not->toHaveKey('regex_pattern');
    expect($data['versionHistory'])->not->toHaveKey('ai_translate');
})->with('attribute types');

it('returns an empty history payload without crashing when the version does not exist', function () {
    $attribute = $this->repository->create([
        'code'         => 'missing_'.uniqid(),
        'type'         => 'text',
        'ai_translate' => 0,
    ]);

    // Request a version id that has no matching audit rows. Previously this
    // fatally errored ("Undefined array key 0") because normalize() blindly
    // read $items[0] on an empty collection.
    $response = $this->controller->getVersionHistoryView('attribute', $attribute->id, 999);

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode($response->getContent(), true);

    expect($data['version'])->toBeNull();
    expect($data['versionHistory'])->toBe([]);
});
