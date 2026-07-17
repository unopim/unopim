<?php

use Webkul\Category\Models\Category;
use Webkul\MagicAI\Services\Prompt\CategoryPrompt;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

/**
 * Regression: image AI generation called from screens with no product/category
 * resource (e.g. attribute edit page) used to crash with
 * `Call to a member function toArray() on null` because the prompt service
 * dereferenced the missing product/category. The guard returns the prompt
 * unchanged so the AI call still proceeds.
 */
afterEach(function () {
    ProductPrompt::resetInstance();
    CategoryPrompt::resetInstance();
});

it('returns the prompt unchanged when the product id does not resolve to a product', function () {
    $prompt = 'Describe @name on a white background';

    $missingId = (int) Product::query()->max('id') + 1;

    $result = ProductPrompt::getInstance()->updatePrompt($prompt, $missingId);

    expect($result)->toBe($prompt);
});

it('returns the prompt unchanged when the category id does not resolve to a category', function () {
    $prompt = 'Translate @name to spanish';

    $missingId = (int) Category::query()->max('id') + 1;

    $result = CategoryPrompt::getInstance()->updatePrompt($prompt, $missingId);

    expect($result)->toBe($prompt);
});
