<?php

beforeEach(function () {
    $this->loginAsAdmin();
});

it('rejects an unsupported provider with a 422 validation error', function () {
    $this->postJson(route('admin.magic_ai.platform.fetch_models'), ['provider' => 'not-a-provider'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('provider');
});
