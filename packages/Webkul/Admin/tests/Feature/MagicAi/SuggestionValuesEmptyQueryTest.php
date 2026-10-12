<?php

/**
 * Regression: typing `@` in the AI image prompt sent an empty query string,
 * which Laravel's ConvertEmptyStringsToNull middleware turned into null,
 * which the strict-typed AttributeRepository::getAttributeListBySearch(string)
 * rejected with a TypeError. The controller now coalesces null to ''.
 */
beforeEach(function () {
    $this->loginAsAdmin();
});

it('returns 200 with a JSON array when the query parameter is missing', function () {
    $this->getJson(route('admin.magic_ai.suggestion_values'))
        ->assertOk()
        ->assertJsonIsArray();
});

it('returns 200 with a JSON array when the query parameter is empty (becomes null after middleware)', function () {
    $this->getJson(route('admin.magic_ai.suggestion_values', ['query' => '']))
        ->assertOk()
        ->assertJsonIsArray();
});

it('handles category_field entity name with an empty query', function () {
    $this->getJson(route('admin.magic_ai.suggestion_values', ['entity_name' => 'category_field']))
        ->assertOk()
        ->assertJsonIsArray();
});
