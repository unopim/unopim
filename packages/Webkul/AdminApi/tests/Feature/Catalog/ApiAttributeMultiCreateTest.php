<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should reject POST /attributes when body is a list of multiple objects (Issue #733)', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attributes.store'), [
            ['type' => 'text', 'code' => 'one'.uniqid()],
            ['type' => 'text', 'code' => 'two'.uniqid()],
        ]);

    $response->assertStatus(422);
});
