<?php

use Webkul\AiAgent\Chat\Tools\EditImage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

describe('EditImage tool parameter and source resolution (Issue #683)', function () {

    it('tool description mentions fetching image from product by SKU', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/EditImage.php')
        );

        // SKU should be a required parameter
        expect($source)->toContain("->withStringParameter('sku'");

        // Tool description should mention fetching from product
        expect($source)->toContain('Fetches the image from the product by SKU');

        // Should NOT require user to upload first
        expect($source)->not->toContain('The user must upload an image first');
    });

    it('tool supports attribute parameter for specifying which image to edit', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/EditImage.php')
        );

        expect($source)->toContain("->withStringParameter('attribute'");
    });

    it('tool supports image_index parameter for gallery attributes', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/EditImage.php')
        );

        expect($source)->toContain("->withStringParameter('image_index'");
    });

    it('resolveImageAttribute auto-detects first image attribute from product family', function () {
        $tool = new EditImage;

        $reflection = new ReflectionMethod($tool, 'resolveImageAttribute');

        expect($reflection->getName())->toBe('resolveImageAttribute');
    });

    it('getAttributeScope correctly identifies attribute scope', function () {
        $tool = new EditImage;

        $method = new ReflectionMethod($tool, 'getAttributeScope');

        // Create a common (non-locale, non-channel) attribute
        $attr = Attribute::factory()->create([
            'type'              => 'image',
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        $scope = $method->invoke($tool, $attr);
        expect($scope)->toBe('common');
    });

    it('getAttributeScope returns locale_specific for locale-based attributes', function () {
        $tool = new EditImage;

        $method = new ReflectionMethod($tool, 'getAttributeScope');

        $attr = Attribute::factory()->create([
            'type'              => 'image',
            'value_per_locale'  => true,
            'value_per_channel' => false,
        ]);

        $scope = $method->invoke($tool, $attr);
        expect($scope)->toBe('locale_specific');
    });

    it('getAttributeScope returns channel_specific for channel-based attributes', function () {
        $tool = new EditImage;

        $method = new ReflectionMethod($tool, 'getAttributeScope');

        $attr = Attribute::factory()->create([
            'type'              => 'image',
            'value_per_locale'  => false,
            'value_per_channel' => true,
        ]);

        $scope = $method->invoke($tool, $attr);
        expect($scope)->toBe('channel_specific');
    });

    it('saveToProduct method exists and handles both image and gallery types', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/EditImage.php')
        );

        // Should have saveToProduct method
        expect($source)->toContain('function saveToProduct');

        // Should handle gallery index replacement
        expect($source)->toContain('$images[$imageIndex] = $storedPath');

        // Should write back to correct scope
        expect($source)->toContain('channel_locale_specific');
        expect($source)->toContain('channel_specific');
        expect($source)->toContain('locale_specific');
    });

    it('agent runner system prompt mentions fetching from product by SKU', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/AgentRunner.php')
        );

        expect($source)->toContain('edit_image with the product SKU');
        expect($source)->toContain('fetches the image from the product');
    });
});
