<?php

use Webkul\MagicAI\Services\Prompt\CategoryPrompt;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;

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

    $result = ProductPrompt::getInstance()->updatePrompt($prompt, 99999);

    expect($result)->toBe($prompt);
});

it('returns the prompt unchanged when the category id does not resolve to a category', function () {
    $prompt = 'Translate @name to spanish';

    $result = CategoryPrompt::getInstance()->updatePrompt($prompt, 99999);

    expect($result)->toBe($prompt);
});
