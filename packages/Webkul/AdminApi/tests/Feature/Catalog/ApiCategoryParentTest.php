<?php

use Webkul\Category\Models\Category;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should include the parent category code when fetching a child category by code (Issue #729)', function () {
    $root = Category::whereNull('parent_id')->first() ?? Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $root->id]);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.categories.get', ['code' => $child->code]));

    $response->assertOk();
    $response->assertJsonPath('parent', $root->code);
});
