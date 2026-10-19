<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Webkul\AiAgent\Http\Requests\ChatRequest;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->actingAs(Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]), 'admin');
    Route::post('/_test/chat-extensions', fn (ChatRequest $request) => response()->json($request->validated()));
});

it('accepts bounded extension selections in array and multipart JSON forms', function (bool $json) {
    $data = ['example' => ['product_id' => 12, 'references' => [['id' => 'asset_7']]]];
    $this->postJson('/_test/chat-extensions', ['message' => 'Preview', 'extensions' => $json ? json_encode($data) : $data])
        ->assertOk()->assertJsonPath('extensions', $data);
    Http::assertNothingSent();
})->with([false, true]);

it('rejects oversized deeply nested or malformed extension selections before execution', function (string $kind, bool $json) {
    $value = match ($kind) {
        'bytes'     => ['example' => ['text' => str_repeat('x', 16385)]],
        'nodes'     => ['example' => array_fill(0, 2048, null)],
        'namespace' => array_fill_keys(range(0, 10), []),
        'reserved'  => ['example' => ['__proto__' => []]],
        default     => ['leaf' => 'value'],
    };
    if ($kind === 'depth') {
        for ($i = 0; $i < 9; $i++) {
            $value = ['nested' => $value];
        }
    }
    $this->postJson('/_test/chat-extensions', ['message' => 'Preview', 'extensions' => $json ? json_encode($value) : $value])
        ->assertUnprocessable()->assertJsonValidationErrors('extensions');
    Http::assertNothingSent();
})->with(['bytes', 'nodes', 'namespace', 'reserved', 'depth'])->with([false, true]);

it('does not silently discard invalid JSON or scalar extension data', function (string $value) {
    $this->postJson('/_test/chat-extensions', ['message' => 'Preview', 'extensions' => $value])
        ->assertUnprocessable()->assertJsonValidationErrors('extensions');
})->with(['{broken', 'null', '"string"', 'false']);

it('rejects extension namespaces the composer registry could never register', function (array $value, bool $json) {
    $this->postJson('/_test/chat-extensions', ['message' => 'Preview', 'extensions' => $json ? json_encode($value) : $value])
        ->assertUnprocessable()->assertJsonValidationErrors('extensions');
})->with([
    'numeric'    => [[0 => ['id' => 1]]],
    'list'       => [[['id' => 1], ['id' => 2]]],
    'uppercase'  => [['Example' => []]],
    'leading'    => [['1example' => []]],
    'symbol'     => [['exa mple' => []]],
    'too long'   => [[str_repeat('x', 65) => []]],
])->with([false, true]);

it('accepts dotted and hyphenated extension namespaces', function () {
    $data = ['example.catalog' => ['id' => 1], 'acme-dam_v2' => []];

    $response = $this->postJson('/_test/chat-extensions', ['message' => 'Preview', 'extensions' => $data])->assertOk();

    expect($response->json('extensions'))->toEqual($data);
});
